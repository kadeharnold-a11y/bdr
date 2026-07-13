import { Router } from "express";
import { db, audit } from "../db/index.js";
import { newId, generateTrackingId } from "../utils/ids.js";
import { requireCitizenAuth } from "../middleware/auth.js";
import { addWorkingDays } from "../utils/workingDays.js";

const router = Router();

// PRD 7: Npontu Pay integration. No sandbox credentials exist yet (PRD 15.2
// assumption), so NPONTU_PAY_MODE=mock lets the whole flow run locally: an
// "initiate" call creates a PENDING payment, and /mock-confirm simulates the
// provider webhook that would otherwise arrive from the real Npontu Pay API.
function isMockMode() {
  return (process.env.NPONTU_PAY_MODE || "mock") !== "live";
}

function confirmPayment(payment, providerRef) {
  const application = db.prepare(`SELECT * FROM applications WHERE id = ?`).get(payment.application_id);
  const now = new Date().toISOString();

  db.prepare(`UPDATE payments SET status = 'SUCCESS', provider_ref = ?, updated_at = ? WHERE id = ?`).run(providerRef, now, payment.id);

  const trackingId = generateTrackingId(application.event_type);
  const config = db.prepare(`SELECT * FROM event_type_config WHERE event_type = ?`).get(application.event_type);
  const durationDays = application.tier === "express" ? config.express_duration_days : config.standard_duration_days;
  const slaDeadline = addWorkingDays(now, durationDays).toISOString();

  db.prepare(`
    UPDATE applications SET status = 'SUBMITTED', tracking_id = ?, sla_deadline = ?, submitted_at = ? WHERE id = ?
  `).run(trackingId, slaDeadline, now, application.id);

  audit({ actorType: "system", action: "PAYMENT_CONFIRMED", entityType: "application", entityId: application.id, details: { trackingId, providerRef } });

  return { trackingId, slaDeadline };
}

router.post("/initiate", requireCitizenAuth, (req, res) => {
  const { applicationId, method } = req.body || {};
  const application = db.prepare(`SELECT * FROM applications WHERE id = ? AND citizen_id = ?`).get(applicationId, req.citizenId);
  if (!application) return res.status(404).json({ error: { code: "NOT_FOUND", message: "Application not found" } });
  if (application.status !== "PAYMENT_PENDING") {
    return res.status(409).json({ error: { code: "NOT_PAYABLE", message: "Application is not awaiting payment" } });
  }

  const existing = db.prepare(`SELECT * FROM payments WHERE application_id = ? AND status = 'PENDING'`).get(applicationId);
  const now = new Date().toISOString();
  let payment = existing;
  if (!payment) {
    const id = newId("pay");
    db.prepare(`
      INSERT INTO payments (id, application_id, method, amount, currency, status, created_at, updated_at)
      VALUES (?, ?, ?, ?, ?, 'PENDING', ?, ?)
    `).run(id, applicationId, method || null, application.fee_amount, application.fee_currency, now, now);
    payment = db.prepare(`SELECT * FROM payments WHERE id = ?`).get(id);
  }

  res.json({
    paymentId: payment.id,
    amount: payment.amount,
    currency: payment.currency,
    mode: isMockMode() ? "mock" : "live",
    // In mock mode there's no real Npontu Pay redirect - the client calls
    // POST /api/payments/mock-confirm to simulate a successful webhook.
    mockConfirmAvailable: isMockMode(),
  });
});

// Dev-only convenience standing in for the real Npontu Pay webhook while no
// sandbox credentials exist. Not part of the real product surface.
router.post("/mock-confirm", requireCitizenAuth, (req, res) => {
  if (!isMockMode()) {
    return res.status(403).json({ error: { code: "MOCK_DISABLED", message: "NPONTU_PAY_MODE is live; use the real webhook" } });
  }
  const { paymentId } = req.body || {};
  const payment = db.prepare(`SELECT * FROM payments WHERE id = ?`).get(paymentId);
  if (!payment) return res.status(404).json({ error: { code: "NOT_FOUND", message: "Payment not found" } });
  const application = db.prepare(`SELECT * FROM applications WHERE id = ? AND citizen_id = ?`).get(payment.application_id, req.citizenId);
  if (!application) return res.status(404).json({ error: { code: "NOT_FOUND", message: "Application not found" } });
  if (payment.status !== "PENDING") {
    return res.status(409).json({ error: { code: "ALREADY_PROCESSED", message: "Payment already processed" } });
  }

  const result = confirmPayment(payment, `MOCK-${newId("txn")}`);
  res.json({ status: "SUCCESS", ...result });
});

// PRD 7.2 step 5: real Npontu Pay webhook -
// {status, transaction_id, amount, currency, timestamp, application_ref}.
// TODO: verify NPONTU_PAY_WEBHOOK_SECRET signature once Npontu Pay's
// signing scheme is documented (not available yet - PRD 15.2).
router.post("/webhook", (req, res) => {
  const { status, transaction_id, application_ref } = req.body || {};
  const payment = db.prepare(`SELECT * FROM payments WHERE application_id = ? AND status = 'PENDING'`).get(application_ref);
  if (!payment) return res.status(404).json({ error: { code: "NOT_FOUND", message: "No pending payment for this application" } });

  if (status !== "SUCCESS") {
    db.prepare(`UPDATE payments SET status = 'FAILED', provider_ref = ?, updated_at = ? WHERE id = ?`).run(transaction_id, new Date().toISOString(), payment.id);
    return res.json({ received: true });
  }

  confirmPayment(payment, transaction_id);
  res.json({ received: true });
});

router.get("/:applicationId/receipt", requireCitizenAuth, (req, res) => {
  const application = db.prepare(`SELECT * FROM applications WHERE id = ? AND citizen_id = ?`).get(req.params.applicationId, req.citizenId);
  if (!application) return res.status(404).json({ error: { code: "NOT_FOUND", message: "Application not found" } });
  const payment = db.prepare(`SELECT * FROM payments WHERE application_id = ? AND status = 'SUCCESS' ORDER BY updated_at DESC`).get(application.id);
  if (!payment) return res.status(404).json({ error: { code: "NOT_FOUND", message: "No completed payment for this application" } });

  const citizen = db.prepare(`SELECT full_name FROM citizens WHERE id = ?`).get(req.citizenId);

  // PRD 7.2 step 7: e-receipt content. Actual PDF rendering isn't built in
  // this v1 slice - this returns the structured data a PDF template would use.
  res.json({
    trackingId: application.tracking_id,
    eventType: application.event_type,
    tier: application.tier,
    amountPaid: payment.amount,
    currency: payment.currency,
    providerRef: payment.provider_ref,
    paidAt: payment.updated_at,
    citizenName: citizen.full_name,
  });
});

export default router;

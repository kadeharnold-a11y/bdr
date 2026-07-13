import { Router } from "express";
import bcrypt from "bcryptjs";
import { db, audit } from "../db/index.js";
import { requireStaffAuth } from "../middleware/auth.js";
import { issueStaffTokens } from "../utils/tokens.js";

const router = Router();

// PRD 9.2 step 6: 2FA is mandatory for all back-office users. Not implemented
// in this v1 slice (no back-office admin UI to provision users through
// either - see the seeding note in db/index.js) - this is password-only.
router.post("/login", async (req, res) => {
  const { staffId, password } = req.body || {};
  const staff = db.prepare(`SELECT * FROM staff_users WHERE staff_id = ? AND active = 1`).get(staffId);
  if (!staff) return res.status(401).json({ error: { code: "INVALID_CREDENTIALS", message: "Staff ID or password is incorrect" } });

  const matches = await bcrypt.compare(password || "", staff.password_hash);
  if (!matches) return res.status(401).json({ error: { code: "INVALID_CREDENTIALS", message: "Staff ID or password is incorrect" } });

  const tokens = issueStaffTokens(staff);
  res.json({ staffId: staff.staff_id, role: staff.role, fullName: staff.full_name, ...tokens });
});

router.use(requireStaffAuth());

// PRD 11.1: dual-lane queue (Standard / Express), SLA-coloured. The full
// stage-by-stage workflow engine (PRD 10) isn't built - this is a
// simplified linear status model (SUBMITTED -> UNDER_REVIEW -> APPROVED ->
// COMPLETED, with CORRECTIONS_REQUIRED/REJECTED branches).
router.get("/queue", (req, res) => {
  const { tier, mine } = req.query;
  const statuses = ["SUBMITTED", "UNDER_REVIEW", "CORRECTIONS_REQUIRED", "AWAITING_APPROVAL"];
  const placeholders = statuses.map(() => "?").join(",");
  let sql = `SELECT * FROM applications WHERE status IN (${placeholders})`;
  const params = [...statuses];

  if (tier) {
    sql += ` AND tier = ?`;
    params.push(tier);
  }
  if (mine === "true") {
    sql += ` AND assigned_staff_id = ?`;
    params.push(req.staffId);
  }
  sql += ` ORDER BY tier DESC, sla_deadline ASC`;

  const rows = db.prepare(sql).all(...params);
  res.json(rows.map(serializeQueueItem));
});

router.get("/applications/:id", (req, res) => {
  const application = db.prepare(`SELECT * FROM applications WHERE id = ?`).get(req.params.id);
  if (!application) return res.status(404).json({ error: { code: "NOT_FOUND", message: "Application not found" } });

  const citizen = db.prepare(`SELECT id, full_name, phone, email, ghana_card_number, nia_status FROM citizens WHERE id = ?`).get(application.citizen_id);
  const documents = db.prepare(`SELECT id, field_name, original_name, mime_type, size_bytes, created_at FROM documents WHERE application_id = ?`).all(application.id);

  res.json({
    ...serializeQueueItem(application),
    formData: JSON.parse(application.form_data),
    citizen,
    documents,
  });
});

// Officer picks up an unassigned application (PRD 11.1: "My Queue" vs "All Queue").
router.post("/applications/:id/claim", (req, res) => {
  const application = db.prepare(`SELECT * FROM applications WHERE id = ?`).get(req.params.id);
  if (!application) return res.status(404).json({ error: { code: "NOT_FOUND", message: "Application not found" } });
  if (application.status !== "SUBMITTED") {
    return res.status(409).json({ error: { code: "NOT_CLAIMABLE", message: "Only newly submitted applications can be claimed" } });
  }

  db.prepare(`UPDATE applications SET status = 'UNDER_REVIEW', assigned_staff_id = ? WHERE id = ?`).run(req.staffId, application.id);
  audit({ actorType: "staff", actorId: req.staffId, action: "APPLICATION_CLAIMED", entityType: "application", entityId: application.id });
  res.json(serializeQueueItem(db.prepare(`SELECT * FROM applications WHERE id = ?`).get(application.id)));
});

// PRD 11.3: request corrections from the citizen. SLA clock "pauses" in the
// PRD; modeled here by simply leaving sla_deadline untouched while status is
// CORRECTIONS_REQUIRED, since no clock-pausing engine exists yet.
router.post("/applications/:id/request-corrections", requireStaffAuth(["REGISTRATION_OFFICER", "SUPERVISOR", "ADMIN"]), (req, res) => {
  const { fields, notes } = req.body || {};
  const application = db.prepare(`SELECT * FROM applications WHERE id = ?`).get(req.params.id);
  if (!application) return res.status(404).json({ error: { code: "NOT_FOUND", message: "Application not found" } });
  if (!["UNDER_REVIEW", "AWAITING_APPROVAL"].includes(application.status)) {
    return res.status(409).json({ error: { code: "INVALID_STATE", message: "Application isn't in review" } });
  }

  db.prepare(`UPDATE applications SET status = 'CORRECTIONS_REQUIRED' WHERE id = ?`).run(application.id);
  audit({ actorType: "staff", actorId: req.staffId, action: "CORRECTIONS_REQUESTED", entityType: "application", entityId: application.id, details: { fields, notes } });
  res.json({ status: "CORRECTIONS_REQUIRED" });
});

router.post("/applications/:id/approve", requireStaffAuth(["REGISTRATION_OFFICER", "SUPERVISOR", "ADMIN"]), (req, res) => {
  const application = db.prepare(`SELECT * FROM applications WHERE id = ?`).get(req.params.id);
  if (!application) return res.status(404).json({ error: { code: "NOT_FOUND", message: "Application not found" } });
  if (!["UNDER_REVIEW", "AWAITING_APPROVAL"].includes(application.status)) {
    return res.status(409).json({ error: { code: "INVALID_STATE", message: "Application isn't in review" } });
  }

  db.prepare(`UPDATE applications SET status = 'APPROVED' WHERE id = ?`).run(application.id);
  audit({ actorType: "staff", actorId: req.staffId, action: "APPLICATION_APPROVED", entityType: "application", entityId: application.id });
  res.json({ status: "APPROVED" });
});

// Stands in for PRD 10.2 stages 5-6 (certificate generation/print/dispatch)
// collapsed into one step - the certificate PDF pipeline itself isn't built.
router.post("/applications/:id/complete", requireStaffAuth(["REGISTRATION_OFFICER", "SUPERVISOR", "ADMIN"]), (req, res) => {
  const application = db.prepare(`SELECT * FROM applications WHERE id = ?`).get(req.params.id);
  if (!application) return res.status(404).json({ error: { code: "NOT_FOUND", message: "Application not found" } });
  if (application.status !== "APPROVED") {
    return res.status(409).json({ error: { code: "INVALID_STATE", message: "Application must be approved first" } });
  }

  db.prepare(`UPDATE applications SET status = 'COMPLETED' WHERE id = ?`).run(application.id);
  audit({ actorType: "staff", actorId: req.staffId, action: "APPLICATION_COMPLETED", entityType: "application", entityId: application.id });
  res.json({ status: "COMPLETED" });
});

router.post("/applications/:id/reject", requireStaffAuth(["REGISTRATION_OFFICER", "SUPERVISOR", "ADMIN"]), (req, res) => {
  const { reason } = req.body || {};
  const application = db.prepare(`SELECT * FROM applications WHERE id = ?`).get(req.params.id);
  if (!application) return res.status(404).json({ error: { code: "NOT_FOUND", message: "Application not found" } });
  if (!reason) return res.status(400).json({ error: { code: "REASON_REQUIRED", message: "A rejection reason is required" } });

  db.prepare(`UPDATE applications SET status = 'REJECTED' WHERE id = ?`).run(application.id);
  audit({ actorType: "staff", actorId: req.staffId, action: "APPLICATION_REJECTED", entityType: "application", entityId: application.id, details: { reason } });
  res.json({ status: "REJECTED" });
});

function slaPercentRemaining(application) {
  if (!application.sla_deadline || !application.submitted_at) return null;
  const total = new Date(application.sla_deadline) - new Date(application.submitted_at);
  const remaining = new Date(application.sla_deadline) - new Date();
  if (total <= 0) return 0;
  return Math.max(0, Math.round((remaining / total) * 100));
}

function serializeQueueItem(row) {
  return {
    id: row.id,
    trackingId: row.tracking_id,
    eventType: row.event_type,
    tier: row.tier,
    status: row.status,
    assignedStaffId: row.assigned_staff_id,
    slaDeadline: row.sla_deadline,
    slaPercentRemaining: slaPercentRemaining(row),
    submittedAt: row.submitted_at,
    createdAt: row.created_at,
  };
}

export default router;

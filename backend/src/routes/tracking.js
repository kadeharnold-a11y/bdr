import { Router } from "express";
import { db } from "../db/index.js";

const router = Router();

// PRD 8.3: public tracking, no login required. Only exposes status/tier/
// event type/dates and the citizen's first name - no other personal data.
router.get("/:trackingId", (req, res) => {
  const application = db.prepare(`SELECT * FROM applications WHERE tracking_id = ?`).get(req.params.trackingId);
  if (!application) {
    return res.status(404).json({ error: { code: "NOT_FOUND", message: "No application found for this tracking ID" } });
  }
  const citizen = db.prepare(`SELECT full_name FROM citizens WHERE id = ?`).get(application.citizen_id);
  const firstName = citizen?.full_name?.split(" ")[0] || null;

  res.json({
    trackingId: application.tracking_id,
    status: application.status,
    tier: application.tier,
    eventType: application.event_type,
    submittedAt: application.submitted_at,
    estimatedCompletionDate: application.sla_deadline,
    citizenFirstName: firstName,
  });
});

export default router;

import { Router } from "express";
import { db } from "../db/index.js";
import { requireCitizenAuth } from "../middleware/auth.js";

const router = Router();
router.use(requireCitizenAuth);

router.get("/me", (req, res) => {
  const citizen = db.prepare(`SELECT id, phone, email, full_name, ghana_card_number, date_of_birth, gender, nia_status, created_at FROM citizens WHERE id = ?`).get(req.citizenId);
  if (!citizen) return res.status(404).json({ error: { code: "NOT_FOUND", message: "Citizen not found" } });
  res.json(citizen);
});

router.patch("/me", (req, res) => {
  const { email } = req.body || {};
  db.prepare(`UPDATE citizens SET email = ? WHERE id = ?`).run(email ?? null, req.citizenId);
  const citizen = db.prepare(`SELECT id, phone, email, full_name, ghana_card_number, date_of_birth, gender, nia_status, created_at FROM citizens WHERE id = ?`).get(req.citizenId);
  res.json(citizen);
});

// PRD 4.3: dashboard should let a citizen understand every application's
// status within 5 seconds - active/draft/completed split, done here rather
// than left for the frontend to derive from a flat list.
router.get("/me/dashboard", (req, res) => {
  const rows = db.prepare(`
    SELECT id, tracking_id, event_type, tier, status, fee_amount, fee_currency, sla_deadline, last_saved_at, created_at, submitted_at
    FROM applications WHERE citizen_id = ? ORDER BY last_saved_at DESC
  `).all(req.citizenId);

  const drafts = rows.filter((a) => a.status === "DRAFT" || a.status === "PAYMENT_PENDING");
  const completed = rows.filter((a) => a.status === "COMPLETED" || a.status === "REJECTED");
  const active = rows.filter((a) => !drafts.includes(a) && !completed.includes(a));

  res.json({
    activeApplications: active,
    drafts,
    completedApplications: completed,
    notifications: [], // TODO: notifications table not built yet in this v1 slice.
  });
});

export default router;

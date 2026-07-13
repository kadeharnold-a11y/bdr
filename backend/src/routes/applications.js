import { Router } from "express";
import multer from "multer";
import path from "node:path";
import { db, audit } from "../db/index.js";
import { newId } from "../utils/ids.js";
import { requireCitizenAuth } from "../middleware/auth.js";
import { FORM_SCHEMAS, validateApplicationForm } from "../config/formSchemas.js";

const router = Router();

const upload = multer({
  dest: path.join(process.cwd(), "uploads"),
  limits: { fileSize: 5 * 1024 * 1024 }, // PRD 6.2 step 6: 5MB max per document.
});

function loadOwnApplication(req, res, next) {
  const app = db.prepare(`SELECT * FROM applications WHERE id = ?`).get(req.params.id);
  if (!app || app.citizen_id !== req.citizenId) {
    return res.status(404).json({ error: { code: "NOT_FOUND", message: "Application not found" } });
  }
  req.application = app;
  next();
}

// Public catalogue - PRD 6.1 tier selection needs fees/SLA before login-gated
// steps, and the event picker (PRD 6.2 step 2) lists all six types.
router.get("/event-types", (req, res) => {
  const rows = db.prepare(`SELECT * FROM event_type_config`).all();
  res.json(
    rows.map((r) => ({
      eventType: r.event_type,
      label: r.label,
      formSupported: !!r.form_supported,
      tiers: {
        standard: { fee: r.standard_fee, durationDays: r.standard_duration_days },
        express: r.express_enabled ? { fee: r.express_fee, durationDays: r.express_duration_days } : null,
      },
    }))
  );
});

router.use(requireCitizenAuth);

router.get("/", (req, res) => {
  const { status } = req.query;
  const rows = status
    ? db.prepare(`SELECT * FROM applications WHERE citizen_id = ? AND status = ? ORDER BY last_saved_at DESC`).all(req.citizenId, status)
    : db.prepare(`SELECT * FROM applications WHERE citizen_id = ? ORDER BY last_saved_at DESC`).all(req.citizenId);
  res.json(rows.map(serializeApplication));
});

// PRD 6.2 steps 2-3: pick event type + tier before any form fields appear.
router.post("/", (req, res) => {
  const { eventType, tier } = req.body || {};
  const config = db.prepare(`SELECT * FROM event_type_config WHERE event_type = ?`).get(eventType);
  if (!config) return res.status(400).json({ error: { code: "INVALID_EVENT_TYPE", message: "Unknown event type" } });
  if (!["standard", "express"].includes(tier)) {
    return res.status(400).json({ error: { code: "INVALID_TIER", message: "Tier must be 'standard' or 'express'" } });
  }
  if (tier === "express" && !config.express_enabled) {
    return res.status(400).json({ error: { code: "EXPRESS_UNAVAILABLE", message: "Express service is not available for this event type" } });
  }

  const id = newId("app");
  const now = new Date().toISOString();
  const feeAmount = tier === "express" ? config.express_fee : config.standard_fee;

  db.prepare(`
    INSERT INTO applications (id, citizen_id, event_type, tier, status, form_data, fee_amount, fee_currency, last_saved_at, created_at)
    VALUES (?, ?, ?, ?, 'DRAFT', '{}', ?, 'GHS', ?, ?)
  `).run(id, req.citizenId, eventType, tier, feeAmount, now, now);

  audit({ actorType: "citizen", actorId: req.citizenId, action: "APPLICATION_CREATED", entityType: "application", entityId: id });
  res.status(201).json(serializeApplication(db.prepare(`SELECT * FROM applications WHERE id = ?`).get(id)));
});

router.get("/:id", loadOwnApplication, (req, res) => {
  const documents = db.prepare(`SELECT id, field_name, original_name, mime_type, size_bytes, created_at FROM documents WHERE application_id = ?`).all(req.application.id);
  res.json({ ...serializeApplication(req.application), documents });
});

// PRD 6.3 Save for Later: autosave (debounced client-side) and manual
// "Save and Continue Later" both land here as a form_data merge. Also used
// to edit a CORRECTIONS_REQUIRED application before calling /resubmit
// (PRD 11.3 step 6).
router.patch("/:id", loadOwnApplication, (req, res) => {
  if (!["DRAFT", "CORRECTIONS_REQUIRED"].includes(req.application.status)) {
    return res.status(409).json({ error: { code: "NOT_EDITABLE", message: "Only draft or corrections-required applications can be edited" } });
  }

  const { formData, tier } = req.body || {};
  const merged = { ...JSON.parse(req.application.form_data), ...(formData || {}) };
  const now = new Date().toISOString();

  // PRD 6.3 Tier Lock: tier can only change pre-payment (DRAFT), never while
  // an already-paid application is back with the citizen for corrections.
  let feeAmount = req.application.fee_amount;
  let newTier = req.application.tier;
  if (req.application.status === "DRAFT" && tier && tier !== req.application.tier) {
    const config = db.prepare(`SELECT * FROM event_type_config WHERE event_type = ?`).get(req.application.event_type);
    if (tier === "express" && !config.express_enabled) {
      return res.status(400).json({ error: { code: "EXPRESS_UNAVAILABLE", message: "Express service is not available for this event type" } });
    }
    newTier = tier;
    feeAmount = tier === "express" ? config.express_fee : config.standard_fee;
  }

  db.prepare(`UPDATE applications SET form_data = ?, tier = ?, fee_amount = ?, last_saved_at = ? WHERE id = ?`)
    .run(JSON.stringify(merged), newTier, feeAmount, now, req.application.id);

  res.json(serializeApplication(db.prepare(`SELECT * FROM applications WHERE id = ?`).get(req.application.id)));
});

router.delete("/:id", loadOwnApplication, (req, res) => {
  if (req.application.status !== "DRAFT") {
    return res.status(409).json({ error: { code: "NOT_EDITABLE", message: "Only draft applications can be deleted" } });
  }
  db.prepare(`DELETE FROM documents WHERE application_id = ?`).run(req.application.id);
  db.prepare(`DELETE FROM applications WHERE id = ?`).run(req.application.id);
  res.status(204).end();
});

// PRD 6.2 step 6: document upload, one file per named slot (e.g. "hospitalBirthNotification").
router.post("/:id/documents", loadOwnApplication, upload.single("file"), (req, res) => {
  if (req.application.status !== "DRAFT") {
    return res.status(409).json({ error: { code: "NOT_EDITABLE", message: "Only draft applications accept document uploads" } });
  }
  const { fieldName } = req.body || {};
  if (!fieldName || !req.file) {
    return res.status(400).json({ error: { code: "MISSING_FILE", message: "fieldName and file are required" } });
  }

  const id = newId("doc");
  db.prepare(`
    INSERT INTO documents (id, application_id, field_name, original_name, stored_path, mime_type, size_bytes, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  `).run(id, req.application.id, fieldName, req.file.originalname, req.file.path, req.file.mimetype, req.file.size, new Date().toISOString());

  res.status(201).json({ id, fieldName, originalName: req.file.originalname, sizeBytes: req.file.size });
});

// Citizen re-downloading their own uploaded document (e.g. to confirm what
// was sent after a corrections request). Scoped through the owning
// application, not a bare document ID lookup, so a citizen can't probe
// other people's document IDs.
router.get("/:id/documents/:documentId", loadOwnApplication, (req, res) => {
  const document = db.prepare(`SELECT * FROM documents WHERE id = ? AND application_id = ?`).get(req.params.documentId, req.application.id);
  if (!document) return res.status(404).json({ error: { code: "NOT_FOUND", message: "Document not found" } });
  res.download(document.stored_path, document.original_name);
});

// PRD 6.2 steps 7-9: review + declaration + move to payment. Tracking ID
// isn't assigned yet - that happens on payment success (PRD 7.2 step 6).
router.post("/:id/submit", loadOwnApplication, (req, res) => {
  if (req.application.status !== "DRAFT") {
    return res.status(409).json({ error: { code: "NOT_SUBMITTABLE", message: "Application already submitted" } });
  }

  const schema = FORM_SCHEMAS[req.application.event_type];
  if (!schema) {
    return res.status(400).json({ error: { code: "UNSUPPORTED_EVENT_TYPE", message: "This event type isn't accepting online applications yet" } });
  }

  const formData = JSON.parse(req.application.form_data);
  const { ok, missingFields } = validateApplicationForm(req.application.event_type, formData);
  if (!ok) {
    return res.status(400).json({ error: { code: "INCOMPLETE_FORM", message: "Required fields are missing", missingFields } });
  }

  const uploadedFields = db.prepare(`SELECT DISTINCT field_name FROM documents WHERE application_id = ?`).all(req.application.id).map((r) => r.field_name);
  const missingDocuments = schema.requiredDocuments.filter((doc) => !uploadedFields.includes(doc));
  if (missingDocuments.length > 0) {
    return res.status(400).json({ error: { code: "MISSING_DOCUMENTS", message: "Required documents are missing", missingDocuments } });
  }

  const now = new Date().toISOString();
  db.prepare(`UPDATE applications SET status = 'PAYMENT_PENDING', last_saved_at = ? WHERE id = ?`).run(now, req.application.id);
  audit({ actorType: "citizen", actorId: req.citizenId, action: "APPLICATION_READY_FOR_PAYMENT", entityType: "application", entityId: req.application.id });

  res.json({
    applicationId: req.application.id,
    status: "PAYMENT_PENDING",
    feeAmount: req.application.fee_amount,
    feeCurrency: req.application.fee_currency,
  });
});

// PRD 11.3 step 6-7: citizen edits (via PATCH) after a corrections request,
// then resubmits. SLA clock resumes (PRD 11.3 step 7) - modeled here as
// simply moving back into UNDER_REVIEW for an officer to pick up again.
router.post("/:id/resubmit", loadOwnApplication, (req, res) => {
  if (req.application.status !== "CORRECTIONS_REQUIRED") {
    return res.status(409).json({ error: { code: "NOT_RESUBMITTABLE", message: "Application is not awaiting corrections" } });
  }
  const now = new Date().toISOString();
  db.prepare(`UPDATE applications SET status = 'UNDER_REVIEW', last_saved_at = ? WHERE id = ?`).run(now, req.application.id);
  audit({ actorType: "citizen", actorId: req.citizenId, action: "APPLICATION_RESUBMITTED", entityType: "application", entityId: req.application.id });
  res.json(serializeApplication(db.prepare(`SELECT * FROM applications WHERE id = ?`).get(req.application.id)));
});

function serializeApplication(row) {
  return {
    id: row.id,
    trackingId: row.tracking_id,
    eventType: row.event_type,
    tier: row.tier,
    status: row.status,
    formData: JSON.parse(row.form_data),
    feeAmount: row.fee_amount,
    feeCurrency: row.fee_currency,
    slaDeadline: row.sla_deadline,
    lastSavedAt: row.last_saved_at,
    createdAt: row.created_at,
    submittedAt: row.submitted_at,
  };
}

export default router;

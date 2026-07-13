import { DatabaseSync } from "node:sqlite";
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import bcrypt from "bcryptjs";

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const dbFile = process.env.DATABASE_FILE || "./data/hbdrp.db";
fs.mkdirSync(path.dirname(path.resolve(dbFile)), { recursive: true });

export const db = new DatabaseSync(dbFile);
db.exec("PRAGMA foreign_keys = ON;");

const schema = fs.readFileSync(path.join(__dirname, "schema.sql"), "utf8");
db.exec(schema);

// Seed the six PRD event types (9.1 fee/duration config is admin-editable in
// principle; these are placeholder defaults per OQ-01/OQ-02 - the real fee
// schedule is still an open question in the PRD). Only early_birth ships
// with a real application form in this v1 slice.
const seedEventTypes = [
  { event_type: "early_birth", label: "Early Birth Registration", standard_fee: 5, express_fee: 50, standard_duration_days: 15, express_duration_days: 3, express_enabled: 1, form_supported: 1 },
  { event_type: "late_birth", label: "Late Birth Registration", standard_fee: 20, express_fee: 100, standard_duration_days: 20, express_duration_days: 5, express_enabled: 1, form_supported: 0 },
  { event_type: "death", label: "Death Registration", standard_fee: 5, express_fee: 50, standard_duration_days: 15, express_duration_days: 3, express_enabled: 1, form_supported: 0 },
  { event_type: "foetal_death", label: "Foetal Death Registration", standard_fee: 5, express_fee: 50, standard_duration_days: 15, express_duration_days: 3, express_enabled: 1, form_supported: 0 },
  { event_type: "adoption", label: "Adoption Registration", standard_fee: 30, express_fee: 150, standard_duration_days: 20, express_duration_days: 7, express_enabled: 1, form_supported: 0 },
  { event_type: "surrogacy", label: "Surrogacy Birth Registration", standard_fee: 30, express_fee: 150, standard_duration_days: 20, express_duration_days: 7, express_enabled: 1, form_supported: 0 },
];

const insertEventType = db.prepare(`
  INSERT OR IGNORE INTO event_type_config
    (event_type, label, standard_fee, express_fee, standard_duration_days, express_duration_days, express_enabled, form_supported)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
`);
for (const et of seedEventTypes) {
  insertEventType.run(
    et.event_type,
    et.label,
    et.standard_fee,
    et.express_fee,
    et.standard_duration_days,
    et.express_duration_days,
    et.express_enabled,
    et.form_supported
  );
}

// Dev convenience: back-office user provisioning (PRD 9.2) isn't built as an
// admin UI yet, so seed one account per role with a known dev password.
// Never do this in production - there's no back-office signup flow at all
// yet, which is a real gap once this needs to run for real.
if (process.env.NODE_ENV !== "production") {
  const staffSeed = [
    { staff_id: "ADM-001", full_name: "Dev Admin", role: "ADMIN" },
    { staff_id: "OFF-001", full_name: "Dev Registration Officer", role: "REGISTRATION_OFFICER" },
    { staff_id: "SUP-001", full_name: "Dev Supervisor", role: "SUPERVISOR" },
    { staff_id: "FIN-001", full_name: "Dev Finance Officer", role: "FINANCE_OFFICER" },
  ];
  const devPassword = "changeme123";
  const insertStaff = db.prepare(`
    INSERT OR IGNORE INTO staff_users (id, staff_id, full_name, email, role, password_hash, active, created_at)
    VALUES (?, ?, ?, NULL, ?, ?, 1, ?)
  `);
  for (const s of staffSeed) {
    const existing = db.prepare(`SELECT id FROM staff_users WHERE staff_id = ?`).get(s.staff_id);
    if (!existing) {
      insertStaff.run(crypto.randomUUID(), s.staff_id, s.full_name, s.role, bcrypt.hashSync(devPassword, 10), new Date().toISOString());
      console.log(`[seed] staff account ${s.staff_id} (${s.role}) password=${devPassword}`);
    }
  }
}

export function nextSequence(key) {
  db.prepare(`INSERT INTO sequences (seq_key, value) VALUES (?, 1)
              ON CONFLICT(seq_key) DO UPDATE SET value = value + 1`).run(key);
  const row = db.prepare(`SELECT value FROM sequences WHERE seq_key = ?`).get(key);
  return row.value;
}

export function audit({ actorType, actorId = null, action, entityType = null, entityId = null, details = null }) {
  db.prepare(`
    INSERT INTO audit_log (id, actor_type, actor_id, action, entity_type, entity_id, details_json, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  `).run(
    crypto.randomUUID(),
    actorType,
    actorId,
    action,
    entityType,
    entityId,
    details ? JSON.stringify(details) : null,
    new Date().toISOString()
  );
}

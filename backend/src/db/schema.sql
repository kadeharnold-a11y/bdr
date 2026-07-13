-- HBDRP backend schema (SQLite via node:sqlite).
-- Field lists here are intentionally generic where the PRD itself was
-- incomplete (see PRD section 5, "Pick from the Attached file shared" -
-- the actual per-event-type field/document lists were never attached).

CREATE TABLE IF NOT EXISTS citizens (
  id TEXT PRIMARY KEY,
  phone TEXT UNIQUE NOT NULL,
  email TEXT,
  full_name TEXT NOT NULL,
  ghana_card_number TEXT,
  date_of_birth TEXT,
  gender TEXT,
  pin_hash TEXT NOT NULL,
  nia_status TEXT NOT NULL DEFAULT 'UNVERIFIED',
  created_at TEXT NOT NULL
);

-- Tracks multi-step registration (PRD 4.1) and login (PRD 4.2) flows before
-- a citizen record exists (registration) or a session is fully authenticated
-- (login). One row per in-progress flow, looked up by opaque token.
CREATE TABLE IF NOT EXISTS auth_sessions (
  token TEXT PRIMARY KEY,
  purpose TEXT NOT NULL CHECK (purpose IN ('register', 'login')),
  phone TEXT NOT NULL,
  citizen_id TEXT,
  otp_code TEXT NOT NULL,
  otp_expires_at INTEGER NOT NULL,
  otp_verified INTEGER NOT NULL DEFAULT 0,
  profile_json TEXT,
  created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS event_type_config (
  event_type TEXT PRIMARY KEY,
  label TEXT NOT NULL,
  standard_fee REAL NOT NULL,
  express_fee REAL NOT NULL,
  standard_duration_days INTEGER NOT NULL,
  express_duration_days INTEGER NOT NULL,
  express_enabled INTEGER NOT NULL DEFAULT 1,
  -- Whether POST /api/applications accepts this event type yet. All six PRD
  -- event types are listed in the catalogue, but only early_birth has a real
  -- form/validation implementation in this v1 slice (see shared/api-contract.md).
  form_supported INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS applications (
  id TEXT PRIMARY KEY,
  tracking_id TEXT UNIQUE,
  citizen_id TEXT NOT NULL,
  event_type TEXT NOT NULL,
  tier TEXT CHECK (tier IN ('standard', 'express')),
  status TEXT NOT NULL DEFAULT 'DRAFT',
  form_data TEXT NOT NULL DEFAULT '{}',
  fee_amount REAL,
  fee_currency TEXT NOT NULL DEFAULT 'GHS',
  sla_deadline TEXT,
  assigned_staff_id TEXT,
  last_saved_at TEXT NOT NULL,
  created_at TEXT NOT NULL,
  submitted_at TEXT,
  FOREIGN KEY (citizen_id) REFERENCES citizens(id)
);

CREATE TABLE IF NOT EXISTS documents (
  id TEXT PRIMARY KEY,
  application_id TEXT NOT NULL,
  field_name TEXT NOT NULL,
  original_name TEXT NOT NULL,
  stored_path TEXT NOT NULL,
  mime_type TEXT,
  size_bytes INTEGER,
  created_at TEXT NOT NULL,
  FOREIGN KEY (application_id) REFERENCES applications(id)
);

CREATE TABLE IF NOT EXISTS payments (
  id TEXT PRIMARY KEY,
  application_id TEXT NOT NULL,
  method TEXT,
  amount REAL NOT NULL,
  currency TEXT NOT NULL DEFAULT 'GHS',
  provider_ref TEXT,
  status TEXT NOT NULL DEFAULT 'PENDING' CHECK (status IN ('PENDING', 'SUCCESS', 'FAILED', 'REFUNDED')),
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (application_id) REFERENCES applications(id)
);

CREATE TABLE IF NOT EXISTS staff_users (
  id TEXT PRIMARY KEY,
  staff_id TEXT UNIQUE NOT NULL,
  full_name TEXT NOT NULL,
  email TEXT,
  role TEXT NOT NULL CHECK (role IN ('ADMIN', 'REGISTRATION_OFFICER', 'SUPERVISOR', 'FINANCE_OFFICER')),
  password_hash TEXT NOT NULL,
  active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL
);

-- Per-year, per-event-type counters for tracking IDs / certificate serials
-- (PRD 8.1: BDR-{YYYY}-{EVENT_CODE}-{6-DIGIT-SEQUENCE}, resets annually).
CREATE TABLE IF NOT EXISTS sequences (
  seq_key TEXT PRIMARY KEY,
  value INTEGER NOT NULL DEFAULT 0
);

-- Minimal audit log (PRD 13: Audit Logging - actor, timestamp, action, before/after).
CREATE TABLE IF NOT EXISTS audit_log (
  id TEXT PRIMARY KEY,
  actor_type TEXT NOT NULL,
  actor_id TEXT,
  action TEXT NOT NULL,
  entity_type TEXT,
  entity_id TEXT,
  details_json TEXT,
  created_at TEXT NOT NULL
);

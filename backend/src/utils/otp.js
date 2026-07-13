import { db } from "../db/index.js";
import { newId } from "./ids.js";

const OTP_TTL_SECONDS = 600; // PRD 4.1: OTP valid for 10 minutes.

function generateCode() {
  return String(Math.floor(100000 + Math.random() * 900000));
}

// Creates an auth_sessions row and "sends" the OTP. There's no SMS gateway
// wired up yet (PRD 13 names Hubtel "or equivalent" but no creds exist - see
// PRD 15.2 assumptions), so in dev we log it and optionally echo it back in
// the API response via DEV_EXPOSE_OTP.
export function startOtpSession({ purpose, phone, citizenId = null, profile = null }) {
  const token = newId(purpose === "register" ? "reg" : "log");
  const code = generateCode();
  const expiresAt = Date.now() + OTP_TTL_SECONDS * 1000;

  db.prepare(`
    INSERT INTO auth_sessions (token, purpose, phone, citizen_id, otp_code, otp_expires_at, otp_verified, profile_json, created_at)
    VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)
  `).run(token, purpose, phone, citizenId, code, expiresAt, profile ? JSON.stringify(profile) : null, Date.now());

  console.log(`[OTP][${purpose}] phone=${phone} code=${code} (expires in ${OTP_TTL_SECONDS}s)`);

  return {
    token,
    code,
    expiresInSeconds: OTP_TTL_SECONDS,
  };
}

export function getSession(token) {
  return db.prepare(`SELECT * FROM auth_sessions WHERE token = ?`).get(token);
}

export function verifyOtp(token, code) {
  const session = getSession(token);
  if (!session) return { ok: false, error: "SESSION_NOT_FOUND" };
  if (Date.now() > session.otp_expires_at) return { ok: false, error: "OTP_EXPIRED" };
  if (session.otp_code !== String(code)) return { ok: false, error: "OTP_INCORRECT" };

  db.prepare(`UPDATE auth_sessions SET otp_verified = 1 WHERE token = ?`).run(token);
  return { ok: true, session };
}

export function updateSessionProfile(token, profile) {
  db.prepare(`UPDATE auth_sessions SET profile_json = ? WHERE token = ?`).run(JSON.stringify(profile), token);
}

export function deleteSession(token) {
  db.prepare(`DELETE FROM auth_sessions WHERE token = ?`).run(token);
}

export const OTP_TTL = OTP_TTL_SECONDS;

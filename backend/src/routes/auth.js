import { Router } from "express";
import bcrypt from "bcryptjs";
import { db } from "../db/index.js";
import { newId } from "../utils/ids.js";
import { isValidGhanaMobile, isValidGhanaCardNumber, isValidPin, isValidEmail } from "../utils/validation.js";
import { startOtpSession, verifyOtp, getSession, updateSessionProfile, deleteSession } from "../utils/otp.js";
import { issueCitizenTokens, verifyRefreshToken } from "../utils/tokens.js";

const router = Router();
const devExposeOtp = () => process.env.DEV_EXPOSE_OTP === "true";

function withDevOtp(payload, code) {
  return devExposeOtp() ? { ...payload, devOtp: code } : payload;
}

// --- Registration (PRD 4.1) ---------------------------------------------

// Step 1: phone (+ optional email), start OTP.
router.post("/register/send-otp", (req, res) => {
  const { phone, email } = req.body || {};
  if (!isValidGhanaMobile(phone)) {
    return res.status(400).json({ error: { code: "INVALID_PHONE", message: "Enter a valid 9-digit Ghana mobile number" } });
  }
  if (email && !isValidEmail(email)) {
    return res.status(400).json({ error: { code: "INVALID_EMAIL", message: "Enter a valid email address" } });
  }

  const existing = db.prepare(`SELECT id FROM citizens WHERE phone = ?`).get(phone);
  if (existing) {
    return res.status(409).json({ error: { code: "PHONE_ALREADY_REGISTERED", message: "This phone number already has an account. Please log in instead." } });
  }

  const { token, code, expiresInSeconds } = startOtpSession({ purpose: "register", phone, profile: { email } });
  res.json(withDevOtp({ registrationToken: token, otpSentTo: phone, otpExpiresInSeconds: expiresInSeconds }, code));
});

// Step 2: verify OTP.
router.post("/register/verify-otp", (req, res) => {
  const { registrationToken, otp } = req.body || {};
  const result = verifyOtp(registrationToken, otp);
  if (!result.ok) {
    return res.status(400).json({ error: { code: result.error, message: "OTP verification failed" } });
  }
  res.json({ profileToken: registrationToken });
});

// Step 3: profile + Ghana Card / NIA check.
// PRD design note (4.1): never let NIA availability block registration - if
// the API is down or the card isn't in NIA yet (common for newborns), accept
// the submission and flag the account for manual confirmation at the first
// in-person appointment instead.
router.post("/register/profile", async (req, res) => {
  const { profileToken, fullName, ghanaCardNumber, email } = req.body || {};
  const session = getSession(profileToken);
  if (!session || session.purpose !== "register" || !session.otp_verified) {
    return res.status(400).json({ error: { code: "INVALID_SESSION", message: "Start registration again" } });
  }
  if (!fullName || typeof fullName !== "string" || fullName.trim().length < 2) {
    return res.status(400).json({ error: { code: "INVALID_NAME", message: "Enter the full name as shown on the Ghana Card" } });
  }
  if (ghanaCardNumber && !isValidGhanaCardNumber(ghanaCardNumber)) {
    return res.status(400).json({ error: { code: "INVALID_GHANA_CARD", message: "Ghana Card number must be in GHA-XXXXXXXXX-X format" } });
  }

  // TODO: call the real NIA API here. No staging credentials exist yet
  // (PRD 15.2 assumption) so this always returns UNAVAILABLE for now.
  const nia = { status: "UNAVAILABLE", dateOfBirth: null, gender: null };

  const priorProfile = session.profile_json ? JSON.parse(session.profile_json) : {};
  updateSessionProfile(profileToken, {
    ...priorProfile,
    fullName: fullName.trim(),
    ghanaCardNumber: ghanaCardNumber || null,
    email: email || priorProfile.email || null,
    niaStatus: nia.status,
  });

  res.json({ profileToken, nia });
});

// Step 4: set PIN, create the citizen account.
router.post("/register/pin", async (req, res) => {
  const { profileToken, pin } = req.body || {};
  const session = getSession(profileToken);
  if (!session || session.purpose !== "register" || !session.otp_verified) {
    return res.status(400).json({ error: { code: "INVALID_SESSION", message: "Start registration again" } });
  }
  if (!isValidPin(pin)) {
    return res.status(400).json({ error: { code: "INVALID_PIN", message: "PIN must be exactly 6 digits" } });
  }

  const profile = session.profile_json ? JSON.parse(session.profile_json) : {};
  if (!profile.fullName) {
    return res.status(400).json({ error: { code: "PROFILE_INCOMPLETE", message: "Complete the profile step before setting a PIN" } });
  }

  const citizenId = newId("cit");
  const pinHash = await bcrypt.hash(pin, 10);

  db.prepare(`
    INSERT INTO citizens (id, phone, email, full_name, ghana_card_number, date_of_birth, gender, pin_hash, nia_status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  `).run(
    citizenId,
    session.phone,
    profile.email || null,
    profile.fullName,
    profile.ghanaCardNumber || null,
    profile.dateOfBirth || null,
    profile.gender || null,
    pinHash,
    profile.niaStatus || "UNVERIFIED",
    new Date().toISOString()
  );

  deleteSession(profileToken);

  const tokens = issueCitizenTokens(citizenId);
  res.status(201).json({ citizenId, ...tokens });
});

// --- Login (PRD 4.2: phone + PIN, then OTP) -----------------------------

router.post("/login/send-otp", (req, res) => {
  const { phone, pin } = req.body || {};
  if (!isValidGhanaMobile(phone) || !isValidPin(pin)) {
    return res.status(400).json({ error: { code: "INVALID_CREDENTIALS", message: "Enter your phone number and 6-digit PIN" } });
  }

  const citizen = db.prepare(`SELECT * FROM citizens WHERE phone = ?`).get(phone);
  if (!citizen) {
    return res.status(401).json({ error: { code: "INVALID_CREDENTIALS", message: "Phone number or PIN is incorrect" } });
  }

  bcrypt.compare(pin, citizen.pin_hash).then((matches) => {
    if (!matches) {
      return res.status(401).json({ error: { code: "INVALID_CREDENTIALS", message: "Phone number or PIN is incorrect" } });
    }
    const { token, code, expiresInSeconds } = startOtpSession({ purpose: "login", phone, citizenId: citizen.id });
    res.json(withDevOtp({ loginToken: token, otpSentTo: phone, otpExpiresInSeconds: expiresInSeconds }, code));
  });
});

router.post("/login/verify-otp", (req, res) => {
  const { loginToken, otp } = req.body || {};
  const result = verifyOtp(loginToken, otp);
  if (!result.ok) {
    return res.status(400).json({ error: { code: result.error, message: "OTP verification failed" } });
  }
  const citizenId = result.session.citizen_id;
  deleteSession(loginToken);
  const tokens = issueCitizenTokens(citizenId);
  res.json({ citizenId, ...tokens });
});

router.post("/refresh", (req, res) => {
  const { refreshToken } = req.body || {};
  try {
    const payload = verifyRefreshToken(refreshToken);
    const tokens = issueCitizenTokens(payload.sub);
    res.json(tokens);
  } catch {
    res.status(401).json({ error: { code: "INVALID_REFRESH_TOKEN", message: "Log in again" } });
  }
});

export default router;

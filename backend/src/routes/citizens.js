import { Router } from "express";

const router = Router();

// Auth routes. Real product uses PHONE + 6-DIGIT PIN for login (confirmed by
// inspecting the live site's /api/auth/login call), NOT phone+password as an
// earlier draft of shared/api-contract.md assumed. Registration still sets up
// a PIN (see PRD 4.1). Update the contract doc to match this if it hasn't been
// updated yet.

// Register - step 1: phone number, start OTP
router.post("/register", (req, res) => {
    const { phone } = req.body;
    // TODO: validate Ghana mobile format, check phone not already registered,
              // send OTP via SMS gateway (Hubtel or equivalent - see PRD 13).
              res.json({ otpSentTo: phone, otpExpiresInSeconds: 600, registrationToken: "reg_stub" });
});

// Register - step 2: verify OTP
router.post("/register/verify-otp", (req, res) => {
    // TODO: validate OTP against registrationToken.
              res.json({ profileToken: "prof_stub" });
});

// Register - step 3: profile + Ghana Card / NIA check
router.post("/register/profile", (req, res) => {
    // TODO: call NIA API. Per PRD design note (4.1): do NOT block registration
              // if NIA is unavailable or card not yet in NIA database - flag for manual
              // review at first in-person appointment instead.
              res.json({
                    nia: { status: "UNAVAILABLE", dateOfBirth: null, gender: null },
                    profileToken: req.body.profileToken || "prof_stub",
              });
});

// Register - step 4: set 6-digit PIN, create account
router.post("/register/pin", (req, res) => {
    // TODO: hash PIN with bcrypt, create citizen record, issue JWTs.
              res.status(201).json({
                    citizenId: "cit_stub",
                    accessToken: "jwt_stub",
                    refreshToken: "jwt_stub",
                    expiresIn: 3600,
              });
});

// Login - step 1: phone + PIN (matches real site, not password)
router.post("/login", (req, res) => {
    const { phone, pin } = req.body;
    // TODO: verify PIN against stored hash for this phone number, then send OTP
              // ('Send verification code' on the real login screen).
              res.json({ otpSentTo: phone, loginToken: "log_stub" });
});

// Login - step 2: verify OTP, issue tokens
router.post("/login/verify-otp", (req, res) => {
    // TODO: validate OTP, issue JWTs.
              res.json({ citizenId: "cit_stub", accessToken: "jwt_stub", refreshToken: "jwt_stub", expiresIn: 3600 });
});

// Dashboard - requires auth middleware once built (attaches req.citizenId)
router.get("/me/dashboard", (req, res) => {
    // TODO: replace with real query once auth middleware is in place.
             res.json({
                   activeApplications: [],
                   drafts: [],
                   completedApplications: [],
                   notifications: [],
             });
});

export default router;

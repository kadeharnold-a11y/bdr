import rateLimit, { ipKeyGenerator } from "express-rate-limit";

// Limits how many OTPs can be requested for a given phone number, to stop
// SMS-bombing a real number (each send is a real cost once Hubtel/etc is
// wired up) - keyed by phone rather than just IP, since a bad actor could
// target one victim's phone from many IPs, or many phones from one IP.
// Falls back to ipKeyGenerator (not raw req.ip) so IPv6 addresses are
// normalized per-subnet rather than trivially bypassable per-address.
export const otpSendLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  limit: 5,
  standardHeaders: true,
  legacyHeaders: false,
  keyGenerator: (req) => req.body?.phone || ipKeyGenerator(req.ip),
  message: { error: { code: "TOO_MANY_REQUESTS", message: "Too many verification codes requested. Try again later." } },
});

// Limits brute-forcing a 6-digit OTP or PIN once a session/token exists.
export const otpVerifyLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  limit: 10,
  standardHeaders: true,
  legacyHeaders: false,
  keyGenerator: (req) => req.body?.registrationToken || req.body?.loginToken || ipKeyGenerator(req.ip),
  message: { error: { code: "TOO_MANY_REQUESTS", message: "Too many attempts. Try again later." } },
});

export const loginLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  limit: 10,
  standardHeaders: true,
  legacyHeaders: false,
  keyGenerator: (req) => req.body?.phone || ipKeyGenerator(req.ip),
  message: { error: { code: "TOO_MANY_REQUESTS", message: "Too many login attempts. Try again later." } },
});

export const staffLoginLimiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  limit: 10,
  standardHeaders: true,
  legacyHeaders: false,
  keyGenerator: (req) => req.body?.staffId || ipKeyGenerator(req.ip),
  message: { error: { code: "TOO_MANY_REQUESTS", message: "Too many login attempts. Try again later." } },
});

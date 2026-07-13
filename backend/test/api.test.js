import { test, before, after } from "node:test";
import assert from "node:assert/strict";
import fs from "node:fs";
import os from "node:os";
import path from "node:path";

// Env vars must be set before app.js (and its db/index.js import) loads,
// since db/index.js opens the SQLite file and seeds config/staff at import
// time. Each test run gets its own throwaway DB file so runs don't collide
// with the dev DB or with each other.
const tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), "hbdrp-test-"));
process.env.NODE_ENV = "test";
process.env.DATABASE_FILE = path.join(tmpDir, "test.db");
process.env.DEV_EXPOSE_OTP = "true";
process.env.JWT_ACCESS_SECRET = "test-access-secret";
process.env.JWT_REFRESH_SECRET = "test-refresh-secret";
process.env.NPONTU_PAY_MODE = "mock";

const { createApp } = await import("../src/app.js");

let server;
let baseUrl;

before(() => {
  const app = createApp();
  server = app.listen(0);
  const { port } = server.address();
  baseUrl = `http://127.0.0.1:${port}/api`;
});

after(() => {
  server.close();
  fs.rmSync(tmpDir, { recursive: true, force: true });
});

async function post(pathname, body, token) {
  const res = await fetch(`${baseUrl}${pathname}`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: JSON.stringify(body ?? {}),
  });
  return { status: res.status, body: await res.json() };
}

async function patch(pathname, body, token) {
  const res = await fetch(`${baseUrl}${pathname}`, {
    method: "PATCH",
    headers: {
      "Content-Type": "application/json",
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: JSON.stringify(body ?? {}),
  });
  return { status: res.status, body: await res.json() };
}

async function get(pathname, token) {
  const res = await fetch(`${baseUrl}${pathname}`, {
    headers: token ? { Authorization: `Bearer ${token}` } : {},
  });
  return { status: res.status, body: await res.json() };
}

// Shared state threaded through the sequential flow below - mirrors a real
// citizen's session from registration through to a completed certificate.
const state = {};

test("health check", async () => {
  const res = await get("/health");
  assert.equal(res.status, 200);
  assert.equal(res.body.status, "ok");
});

test("registration: send-otp rejects an invalid phone", async () => {
  const res = await post("/auth/register/send-otp", { phone: "123" });
  assert.equal(res.status, 400);
  assert.equal(res.body.error.code, "INVALID_PHONE");
});

test("registration: send-otp succeeds and exposes devOtp", async () => {
  const res = await post("/auth/register/send-otp", { phone: "244111222", email: "test@example.com" });
  assert.equal(res.status, 200);
  assert.ok(res.body.registrationToken);
  assert.ok(res.body.devOtp);
  state.registrationToken = res.body.registrationToken;
  state.otp = res.body.devOtp;
});

test("registration: verify-otp rejects a wrong code", async () => {
  const res = await post("/auth/register/verify-otp", { registrationToken: state.registrationToken, otp: "000000" });
  assert.equal(res.status, 400);
  assert.equal(res.body.error.code, "OTP_INCORRECT");
});

test("registration: verify-otp succeeds with the right code", async () => {
  const res = await post("/auth/register/verify-otp", { registrationToken: state.registrationToken, otp: state.otp });
  assert.equal(res.status, 200);
  assert.equal(res.body.profileToken, state.registrationToken);
});

test("registration: profile step always returns NIA UNAVAILABLE (no NIA integration yet)", async () => {
  const res = await post("/auth/register/profile", {
    profileToken: state.registrationToken,
    fullName: "Kwame Mensah",
    ghanaCardNumber: "GHA-000111222-3",
  });
  assert.equal(res.status, 200);
  assert.equal(res.body.nia.status, "UNAVAILABLE");
});

test("registration: pin step creates the citizen and issues tokens", async () => {
  const res = await post("/auth/register/pin", { profileToken: state.registrationToken, pin: "135790" });
  assert.equal(res.status, 201);
  assert.ok(res.body.citizenId);
  assert.ok(res.body.accessToken);
  state.citizenId = res.body.citizenId;
  state.accessToken = res.body.accessToken;
});

test("registration: same phone number now rejects with PHONE_ALREADY_REGISTERED", async () => {
  const res = await post("/auth/register/send-otp", { phone: "244111222" });
  assert.equal(res.status, 409);
  assert.equal(res.body.error.code, "PHONE_ALREADY_REGISTERED");
});

test("login: wrong PIN is rejected", async () => {
  const res = await post("/auth/login/send-otp", { phone: "244111222", pin: "000000" });
  assert.equal(res.status, 401);
});

test("login: correct PIN + OTP issues a fresh session", async () => {
  const send = await post("/auth/login/send-otp", { phone: "244111222", pin: "135790" });
  assert.equal(send.status, 200);
  const verify = await post("/auth/login/verify-otp", { loginToken: send.body.loginToken, otp: send.body.devOtp });
  assert.equal(verify.status, 200);
  assert.equal(verify.body.citizenId, state.citizenId);
});

test("applications: event-types catalogue lists all six PRD event types", async () => {
  const res = await get("/applications/event-types");
  assert.equal(res.status, 200);
  assert.equal(res.body.length, 6);
  const earlyBirth = res.body.find((e) => e.eventType === "early_birth");
  assert.equal(earlyBirth.formSupported, true);
});

test("applications: create a draft and save form data", async () => {
  const created = await post("/applications", { eventType: "early_birth", tier: "standard" }, state.accessToken);
  assert.equal(created.status, 201);
  assert.equal(created.body.status, "DRAFT");
  state.applicationId = created.body.id;

  const patched = await patch(
    `/applications/${state.applicationId}`,
    {
      formData: {
        childFullName: "Baby Mensah",
        childSex: "M",
        childDateOfBirth: "2026-05-01",
        placeOfBirth: "37 Military Hospital",
        motherFullName: "Kwame Mensah",
        motherGhanaCardNumber: "GHA-000111222-3",
        informantFullName: "Kwame Mensah",
        informantRelationshipToChild: "Father",
        informantPhone: "244111222",
      },
    },
    state.accessToken
  );
  assert.equal(patched.status, 200);
});

test("applications: submit fails until required documents are uploaded", async () => {
  const res = await post(`/applications/${state.applicationId}/submit`, {}, state.accessToken);
  assert.equal(res.status, 400);
  assert.equal(res.body.error.code, "MISSING_DOCUMENTS");
});

test("applications: document upload then submit succeeds", async () => {
  for (const fieldName of ["hospitalBirthNotification", "parentGhanaCardCopy"]) {
    const form = new FormData();
    form.append("fieldName", fieldName);
    form.append("file", new Blob(["test file bytes"], { type: "application/pdf" }), "doc.pdf");
    const res = await fetch(`${baseUrl}/applications/${state.applicationId}/documents`, {
      method: "POST",
      headers: { Authorization: `Bearer ${state.accessToken}` },
      body: form,
    });
    assert.equal(res.status, 201);
    const uploaded = await res.json();
    if (fieldName === "hospitalBirthNotification") state.documentId = uploaded.id;
  }

  const submitted = await post(`/applications/${state.applicationId}/submit`, {}, state.accessToken);
  assert.equal(submitted.status, 200);
  assert.equal(submitted.body.status, "PAYMENT_PENDING");
});

test("applications: citizen can download their own document, not someone else's", async () => {
  const ownDoc = await fetch(`${baseUrl}/applications/${state.applicationId}/documents/${state.documentId}`, {
    headers: { Authorization: `Bearer ${state.accessToken}` },
  });
  assert.equal(ownDoc.status, 200);
  assert.equal(await ownDoc.text(), "test file bytes");

  const wrongApp = await fetch(`${baseUrl}/applications/does-not-exist/documents/${state.documentId}`, {
    headers: { Authorization: `Bearer ${state.accessToken}` },
  });
  assert.equal(wrongApp.status, 404);
});

test("payments: mock-confirm generates a tracking ID matching the PRD format", async () => {
  const initiate = await post("/payments/initiate", { applicationId: state.applicationId, method: "momo" }, state.accessToken);
  assert.equal(initiate.status, 200);

  const confirm = await post("/payments/mock-confirm", { paymentId: initiate.body.paymentId }, state.accessToken);
  assert.equal(confirm.status, 200);
  assert.match(confirm.body.trackingId, /^BDR-\d{4}-EB-\d{6}$/);
  state.trackingId = confirm.body.trackingId;
});

test("tracking: public lookup requires no auth and hides most personal data", async () => {
  const res = await fetch(`${baseUrl}/tracking/${state.trackingId}`);
  const body = await res.json();
  assert.equal(res.status, 200);
  assert.equal(body.status, "SUBMITTED");
  assert.equal(body.citizenFirstName, "Kwame");
  assert.equal(body.citizenName, undefined);
});

test("staff: login and process the application through to completion", async () => {
  const login = await post("/staff/login", { staffId: "OFF-001", password: "changeme123" });
  assert.equal(login.status, 200);
  const staffToken = login.body.accessToken;

  const claim = await post(`/staff/applications/${state.applicationId}/claim`, {}, staffToken);
  assert.equal(claim.status, 200);
  assert.equal(claim.body.status, "UNDER_REVIEW");

  const approve = await post(`/staff/applications/${state.applicationId}/approve`, {}, staffToken);
  assert.equal(approve.status, 200);

  const complete = await post(`/staff/applications/${state.applicationId}/complete`, {}, staffToken);
  assert.equal(complete.status, 200);
  assert.equal(complete.body.status, "COMPLETED");

  const doc = await fetch(`${baseUrl}/staff/applications/${state.applicationId}/documents/${state.documentId}`, {
    headers: { Authorization: `Bearer ${staffToken}` },
  });
  assert.equal(doc.status, 200);
  assert.equal(await doc.text(), "test file bytes");
});

test("staff: non-admin cannot read admin config", async () => {
  const login = await post("/staff/login", { staffId: "OFF-001", password: "changeme123" });
  const res = await get("/staff/admin/event-types", login.body.accessToken);
  assert.equal(res.status, 403);
});

test("staff admin: fee change requires a reason and validates express > standard", async () => {
  const login = await post("/staff/login", { staffId: "ADM-001", password: "changeme123" });
  const adminToken = login.body.accessToken;

  const noReason = await patch("/staff/admin/event-types/early_birth", { standardFee: 8 }, adminToken);
  assert.equal(noReason.status, 400);
  assert.equal(noReason.body.error.code, "REASON_REQUIRED");

  const invalid = await patch("/staff/admin/event-types/early_birth", { expressFee: 1, reason: "test" }, adminToken);
  assert.equal(invalid.status, 400);
  assert.equal(invalid.body.error.code, "INVALID_FEE");

  const ok = await patch("/staff/admin/event-types/early_birth", { standardFee: 8, reason: "test adjustment" }, adminToken);
  assert.equal(ok.status, 200);
  assert.equal(ok.body.standardFee, 8);
});

test("applications: a second event type (death) works through the same generic routes", async () => {
  const eventTypes = await get("/applications/event-types");
  const death = eventTypes.body.find((e) => e.eventType === "death");
  assert.equal(death.formSupported, true);

  const created = await post("/applications", { eventType: "death", tier: "express" }, state.accessToken);
  assert.equal(created.status, 201);
  const applicationId = created.body.id;

  const submitEmpty = await post(`/applications/${applicationId}/submit`, {}, state.accessToken);
  assert.equal(submitEmpty.status, 400);
  assert.equal(submitEmpty.body.error.code, "INCOMPLETE_FORM");
  assert.ok(submitEmpty.body.error.missingFields.includes("causeOfDeath"));

  await patch(
    `/applications/${applicationId}`,
    {
      formData: {
        deceasedFullName: "Yaw Boateng",
        dateOfDeath: "2026-06-20",
        placeOfDeath: "Komfo Anokye Teaching Hospital",
        causeOfDeath: "Certified by attending physician",
        informantFullName: "Kwame Mensah",
        informantRelationshipToDeceased: "Son",
        informantPhone: "244111222",
      },
    },
    state.accessToken
  );

  for (const fieldName of ["medicalCertificateOfCause", "deceasedIdCopy"]) {
    const form = new FormData();
    form.append("fieldName", fieldName);
    form.append("file", new Blob(["test file bytes"], { type: "application/pdf" }), "doc.pdf");
    const res = await fetch(`${baseUrl}/applications/${applicationId}/documents`, {
      method: "POST",
      headers: { Authorization: `Bearer ${state.accessToken}` },
      body: form,
    });
    assert.equal(res.status, 201);
  }

  const submitted = await post(`/applications/${applicationId}/submit`, {}, state.accessToken);
  assert.equal(submitted.status, 200);

  const initiate = await post("/payments/initiate", { applicationId, method: "momo" }, state.accessToken);
  const confirm = await post("/payments/mock-confirm", { paymentId: initiate.body.paymentId }, state.accessToken);
  assert.match(confirm.body.trackingId, /^BDR-\d{4}-DR-\d{6}$/);
});

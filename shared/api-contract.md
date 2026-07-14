# HBDRP API Contract (v1 slice)

This documents what the **Laravel** backend in `backend/` actually
implements today, for whoever is wiring up the frontend. It is **not** the
full PRD scope - see "Known gaps" at the bottom.

(History: this API was first prototyped in Node/Express - that version
lives on the `feature/backend-scaffold` branch. The contract below is
identical across both; the Laravel rebuild replaced the stack, not the API.)

Base URL (dev): `http://localhost:4000/api`

All error responses share this shape:
```json
{ "error": { "code": "SOME_CODE", "message": "Human readable message" } }
```

## Auth

Citizen auth is **phone + 6-digit PIN**, confirmed against the real live
site's behavior (not phone + password, which an earlier draft assumed).

### Register
1. `POST /auth/register/send-otp` `{ phone, email? }` → `{ registrationToken, otpSentTo, otpExpiresInSeconds }`
2. `POST /auth/register/verify-otp` `{ registrationToken, otp }` → `{ profileToken }`
3. `POST /auth/register/profile` `{ profileToken, fullName, ghanaCardNumber?, email? }` → `{ profileToken, nia: { status, dateOfBirth, gender } }`
4. `POST /auth/register/pin` `{ profileToken, pin }` → `{ citizenId, accessToken, refreshToken, expiresIn }`

`phone` is a **9-digit local number**, no `+233` or leading `0` (matches the
validation already in `SignUp.vue`). `ghanaCardNumber` format: `GHA-XXXXXXXXX-X`.

While `DEV_EXPOSE_OTP=true` (dev default), every OTP response also includes
`devOtp` so you can test without a real SMS gateway. Never enable that flag
outside local dev.

### Login
1. `POST /auth/login/send-otp` `{ phone, pin }` → `{ loginToken, otpSentTo, otpExpiresInSeconds }`
2. `POST /auth/login/verify-otp` `{ loginToken, otp }` → `{ citizenId, accessToken, refreshToken, expiresIn }`

### Refresh
`POST /auth/refresh` `{ refreshToken }` → `{ accessToken, refreshToken, expiresIn }`

All authenticated citizen endpoints take `Authorization: Bearer <accessToken>`.

## Citizens

- `GET /citizens/me` → citizen profile
- `PATCH /citizens/me` `{ email }` → updated profile
- `GET /citizens/me/dashboard` → `{ activeApplications[], drafts[], completedApplications[], notifications[] }`

## Applications

- `GET /applications/event-types` (public) → catalogue of all 6 PRD event types with fees/SLA durations. Only `early_birth` and `death` have `formSupported: true` right now - the rest are listed but rejected on submit.
- `POST /applications` `{ eventType, tier }` → creates a `DRAFT` application
- `GET /applications` → list own applications, optional `?status=`
- `GET /applications/:id` → full detail incl. `documents[]`
- `PATCH /applications/:id` `{ formData?, tier? }` → merges into the draft's form data (used for both autosave and "Save and Continue Later"); also how a citizen edits after a `CORRECTIONS_REQUIRED` response
- `DELETE /applications/:id` → discard a draft
- `POST /applications/:id/documents` (multipart: `fieldName`, `file`, max 5MB) → attach a document
- `GET /applications/:id/documents/:documentId` → download a previously uploaded document (own application only)
- `POST /applications/:id/submit` → validates required fields/documents, moves `DRAFT` → `PAYMENT_PENDING`
- `POST /applications/:id/resubmit` → moves `CORRECTIONS_REQUIRED` → `UNDER_REVIEW` after the citizen has edited

### Form fields (v1 placeholders)

**The PRD's own field lists (sections 5A-5F) were never attached** - they
just say "Pick from the Attached file shared" with no attachment. The
fields below are reasonable placeholders pending the real lists, defined
in `backend/config/form_schemas.php`:

**early_birth** - required `formData`: `childFullName`, `childSex`,
`childDateOfBirth`, `placeOfBirth`, `motherFullName`,
`motherGhanaCardNumber`, `informantFullName`,
`informantRelationshipToChild`, `informantPhone`. Required documents:
`hospitalBirthNotification`, `parentGhanaCardCopy`.

**death** - required `formData`: `deceasedFullName`, `dateOfDeath`,
`placeOfDeath`, `causeOfDeath`, `informantFullName`,
`informantRelationshipToDeceased`, `informantPhone`. Required documents:
`medicalCertificateOfCause`, `deceasedIdCopy`.

## Payments (Npontu Pay)

No sandbox credentials exist yet, so payments run in mock mode
(`NPONTU_PAY_MODE=mock`, the default):

- `POST /payments/initiate` `{ applicationId, method }` → `{ paymentId, amount, currency, mode, mockConfirmAvailable }`
- `POST /payments/mock-confirm` `{ paymentId }` (mock mode only) → simulates a successful provider webhook, generates the tracking ID, moves the application to `SUBMITTED`
- `POST /payments/webhook` (real Npontu Pay webhook shape: `{ status, transaction_id, amount, currency, timestamp, application_ref }`) - signature verification is a TODO, no signing scheme documented yet
- `GET /payments/:applicationId/receipt` → e-receipt data (no PDF rendering yet, just the structured fields a template would use)

## Tracking (public, no auth)

`GET /tracking/:trackingId` → `{ trackingId, status, tier, eventType, submittedAt, estimatedCompletionDate, citizenFirstName }`

Tracking ID format: `BDR-{YYYY}-{EVENT_CODE}-{6-digit sequence}`, e.g.
`BDR-2026-EB-000001`. Event codes: `EB`/`LB`/`DR`/`FD`/`AD`/`SR`.

## Application status values

`DRAFT`, `PAYMENT_PENDING`, `SUBMITTED`, `UNDER_REVIEW`,
`CORRECTIONS_REQUIRED`, `APPROVED`, `COMPLETED`, `REJECTED`.

(The PRD lists more granular stages - `Verifying Documents`, `Awaiting
Approval`, `Certificate Printing`, `Ready for Collection`, `Out for
Delivery` - collapsed here; see "Known gaps".)

## Back-office (staff)

- `POST /staff/login` `{ staffId, password }` → `{ staffId, role, fullName, accessToken, expiresIn }` (no 2FA yet - see gaps)
- `GET /staff/queue?tier=&mine=true` → applications in `SUBMITTED`/`UNDER_REVIEW`/`CORRECTIONS_REQUIRED`/`AWAITING_APPROVAL`, each with `slaPercentRemaining`
- `GET /staff/applications/:id` → full detail incl. citizen info and documents
- `GET /staff/applications/:id/documents/:documentId` → download a document (any staff role, for the application workspace's document viewer, PRD 11.2)
- `POST /staff/applications/:id/claim` → `SUBMITTED` → `UNDER_REVIEW`, assigns to caller
- `POST /staff/applications/:id/request-corrections` `{ fields, notes }` → → `CORRECTIONS_REQUIRED`
- `POST /staff/applications/:id/approve` → → `APPROVED`
- `POST /staff/applications/:id/complete` → → `COMPLETED`
- `POST /staff/applications/:id/reject` `{ reason }` → → `REJECTED`

Dev seed accounts (non-production only, see `backend/database/seeders/DatabaseSeeder.php`):
`ADM-001` / `OFF-001` / `SUP-001` / `FIN-001`, all password `changeme123`.

### Admin config (PRD 9.1, ADMIN role only)

- `GET /staff/admin/event-types` → full config per event type (fees, SLA durations, express toggle)
- `PATCH /staff/admin/event-types/:eventType` `{ standardFee?, expressFee?, standardDurationDays?, expressDurationDays?, expressEnabled?, reason? }` → updates config; `reason` is required whenever a fee changes (PRD 9.1.1 "Fee Change Reason"). Validates `expressFee > standardFee` and `expressDurationDays < standardDurationDays`. Changes only affect new applications - in-flight ones keep their locked-in fee/tier.

## Security notes

- CORS is restricted to `FRONTEND_ORIGIN` (comma-separated list, defaults to `http://localhost:5173`) - requests from other origins are rejected.
- OTP-send, OTP-verify, citizen login, and staff login are all rate-limited (see `backend/app/Providers/AppServiceProvider.php`) to slow down SMS-bombing and brute-force attempts.

## Known gaps vs. the full PRD

Deliberately out of scope for this v1 slice - flag before assuming any of
this exists:

- Only **Early Birth Registration** and **Death Registration** have real
  forms/validation. The other 4 event types are listed in the catalogue
  but rejected on submit.
- No **dynamic workflow engine** (PRD section 10) - no configurable
  per-stage SLAs, no auto-escalation, no auto-advance-to-certificate safety
  net. Application status is a simplified linear model instead.
- No **admin configuration UI** for fees/tiers/workflow templates (PRD 9) -
  `event_type_config` is seeded with placeholder values, editable only by
  hand in the DB.
- No **NIA API integration** - always returns `UNAVAILABLE` (correctly,
  per the PRD's own design note, this doesn't block registration).
- No **real SMS gateway** - OTPs are logged server-side and optionally
  echoed in dev responses.
- No **real Npontu Pay integration** - mock mode only, no sandbox creds.
- No **2FA** for back-office users.
- No **certificate PDF generation**, QR codes, or digital signing.
- No **back-office admin UI** for provisioning staff accounts - seeded dev
  accounts only.
- SLA "working days" math skips weekends only - no Ghana public holidays
  calendar (PRD 9.1.2) since there's no admin UI to configure one.

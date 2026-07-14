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
1. `POST /auth/register/send-otp` `{ phone, email?, channel? }` → `{ registrationToken, otpChannel, otpSentTo, otpExpiresInSeconds }`
2. `POST /auth/register/verify-otp` `{ registrationToken, otp }` → `{ profileToken }`
3. `POST /auth/register/profile` `{ profileToken, fullName, ghanaCardNumber?, email? }` → `{ profileToken, nia: { status, dateOfBirth, gender } }`
4. `POST /auth/register/pin` `{ profileToken, pin }` → `{ citizenId, accessToken, refreshToken, expiresIn }`

`phone` is a **9-digit local number**, no `+233` or leading `0` (matches the
validation already in `SignUp.vue`). `ghanaCardNumber` format: `GHA-XXXXXXXXX-X`.

`channel` is `'phone'` (default) or `'email'` — picks where the OTP is
delivered. `channel: 'email'` requires a valid `email`. **Email delivery is
real** (Laravel Mail) once SMTP credentials are configured in `.env`
(`MAIL_MAILER=smtp` + host/username/password); until then it falls back to
Laravel's `log` driver, which writes the full email to
`storage/logs/laravel.log` instead of sending it. **Phone delivery is
always log-only** — no SMS gateway account exists (PRD 13/15.2).

While `DEV_EXPOSE_OTP=true` (dev default), every OTP response also includes
`devOtp` so you can test without configuring real delivery. Never enable
that flag outside local dev.

### Login
1. `POST /auth/login/send-otp` `{ phone, pin, channel? }` → `{ loginToken, otpChannel, otpSentTo, otpExpiresInSeconds }`
2. `POST /auth/login/verify-otp` `{ loginToken, otp }` → `{ citizenId, accessToken, refreshToken, expiresIn }`

`channel: 'email'` on login sends to whatever email is already on the
citizen's account (not user-supplied) — returns `NO_EMAIL_ON_FILE` (400) if
they registered without one.

### Refresh
`POST /auth/refresh` `{ refreshToken }` → `{ accessToken, refreshToken, expiresIn }`

All authenticated citizen endpoints take `Authorization: Bearer <accessToken>`.

### Logout
`POST /auth/logout` (citizen, authenticated) → revokes **every** token for
that citizen (access + refresh, all devices - there's no per-device session
model). `POST /staff/logout` similarly, but only revokes the token used to
call it.

## Citizens

- `GET /citizens/me` → citizen profile
- `PATCH /citizens/me` `{ email }` → updated profile
- `GET /citizens/me/dashboard` → `{ activeApplications[], drafts[], completedApplications[], notifications[] }` - `notifications` here is just the unread feed (max 10), for the dashboard bell icon
- `GET /citizens/me/notifications` → full notification history (read + unread)
- `POST /citizens/me/notifications/:id/read` → marks one read

Notification objects: `{ id, type, title, body, applicationId, read, createdAt }`.
`type` is one of `APPLICATION_SUBMITTED`, `CORRECTIONS_REQUIRED`,
`APPLICATION_APPROVED`, `CERTIFICATE_READY`, `APPLICATION_REJECTED` -
created automatically at those points in the application lifecycle
(PRD 10.4's citizen-facing notification rows). These are in-app/dashboard
only - no SMS delivery, since there's no gateway (see Known Gaps).

## Applications

- `GET /applications/event-types` (public) → catalogue of all 6 PRD event types with fees/SLA durations. All 6 now have `formSupported: true`.
- `POST /applications` `{ eventType, tier }` → creates a `DRAFT` application
- `GET /applications` → list own applications, optional `?status=`
- `GET /applications/:id` → full detail incl. `documents[]`
- `PATCH /applications/:id` `{ formData?, tier? }` → merges into the draft's form data (used for both autosave and "Save and Continue Later"); also how a citizen edits after a `CORRECTIONS_REQUIRED` response
- `DELETE /applications/:id` → discard a draft
- `POST /applications/:id/documents` (multipart: `fieldName`, `file`, max 5MB) → attach a document
- `GET /applications/:id/documents/:documentId` → download a previously uploaded document (own application only)
- `POST /applications/:id/submit` → validates required fields/documents, moves `DRAFT` → `PAYMENT_PENDING`
- `POST /applications/:id/resubmit` → moves `CORRECTIONS_REQUIRED` → `UNDER_REVIEW` after the citizen has edited
- `GET /applications/:id/certificate` → download the certificate PDF once `COMPLETED` (404 before then)

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

**late_birth** - required `formData`: `childFullName`, `childSex`,
`childDateOfBirth`, `placeOfBirth`, `motherFullName`, `fatherFullName`,
`reasonForLateRegistration`, `informantFullName`,
`informantRelationshipToChild`, `informantPhone`. Required documents:
`swornDeclarationOfLateBirth`, `proofOfBirthRecord`, `parentGhanaCardCopy`.

**foetal_death** - required `formData`: `motherFullName`,
`motherGhanaCardNumber`, `gestationalAgeWeeks`, `dateOfFoetalDeath`,
`facilityName`, `informantFullName`, `informantPhone`. Required documents:
`medicalCertificateFoetalDeath`, `motherIdCopy`.

**adoption** - required `formData`: `childFullName`, `childDateOfBirth`,
`adoptiveMotherFullName`, `adoptiveFatherFullName`, `courtOrderReference`,
`courtName`, `informantFullName`, `informantPhone`. Required documents:
`courtAdoptionOrder`, `adoptiveParentGhanaCardCopy`.

**surrogacy** - required `formData`: `childFullName`, `childDateOfBirth`,
`intendedMotherFullName`, `intendedFatherFullName`,
`surrogacyAgreementReference`, `facilityName`, `informantFullName`,
`informantPhone`. Required documents: `surrogacyAgreementDocument`,
`hospitalBirthNotification`, `intendedParentGhanaCardCopy`.

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

## Certificates (PRD 12)

Generated automatically when staff mark an application `COMPLETED`
(`POST /staff/applications/:id/complete` now also returns a `certificate`
object: `{ id, serial, applicationId, issuedAt }`). Certificate serial
format: `CERT-{YYYY}-{EVENT_CODE}-{6-digit sequence}` - a **separate**
sequence from the tracking ID, even for the same application.

- `GET /certificates/verify/:serial` (public, no auth) → `{ serial, valid, registrationType, registeredName, eventDate, registrationDate, issuedAt }`. Deliberately minimal, mirrors the tracking endpoint's data minimization - no phone/email/Ghana Card/full form data.
- `GET /applications/:id/certificate` (citizen, own) and `GET /staff/applications/:id/certificate` (any staff role) → download the actual PDF.

The PDF embeds a QR code pointing at the verify endpoint above, and BDR
branding/seal text, but **no real PKI digital signature** - that needs an
actual BDR-held signing key/HSM, out of scope for this v1 slice (see Known
Gaps). Field lists per event type mirror the same documented placeholders
as the application form itself (`backend/app/Support/CertificateBuilder.php`).

## Application status values

`DRAFT`, `PAYMENT_PENDING`, `SUBMITTED`, `UNDER_REVIEW`,
`CORRECTIONS_REQUIRED`, `APPROVED`, `COMPLETED`, `REJECTED`.

(The PRD lists more granular stages - `Verifying Documents`, `Awaiting
Approval`, `Certificate Printing`, `Ready for Collection`, `Out for
Delivery` - collapsed here; see "Known gaps".)

## Back-office (staff)

### Staff login (PRD 9.2 step 6: 2FA is mandatory, always two steps)

1. `POST /staff/login` `{ staffId, password }`:
   - First-ever login for that account → `{ twoFactorSetupRequired: true, challengeToken, secret, otpauthUrl }`. `otpauthUrl` is an `otpauth://` URI - render it as a QR code for the user to scan into an authenticator app (Google Authenticator, Authy, etc). `secret` is only ever shown here; store the QR code, not the raw secret, in your UI.
   - Already enrolled → `{ twoFactorRequired: true, challengeToken }`.
2. `POST /staff/login/verify-2fa` `{ challengeToken, code }` → `{ staffId, role, fullName, accessToken, expiresIn }`. On first-ever login this also confirms/activates the 2FA secret generated in step 1. `challengeToken` expires after 10 minutes.

`POST /staff/logout` revokes the current token (see Logout section above).
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

### Staff account management (PRD 9.2, ADMIN role only)

- `GET /staff/admin/users` → list all staff accounts
- `POST /staff/admin/users` `{ staffId, fullName, role, email? }` → creates an account with a random temporary password, returned **once** in the response (`temporaryPassword`) and never retrievable again. If `email` is given, sends a real invite email (same mock/real-SMTP fallback as OTP email) with the staff ID, role, and temporary password.
- `PATCH /staff/admin/users/:id` `{ fullName?, role?, active? }` → updates a staff account. Setting `active: false` immediately revokes their ability to log in and **reassigns any of their in-progress applications** (`UNDER_REVIEW`/`CORRECTIONS_REQUIRED`/`AWAITING_APPROVAL`) to an active `SUPERVISOR` (PRD 9.2 step 7) - there's no per-officer supervisor hierarchy modeled, so this picks any active supervisor, not necessarily "their" supervisor.

Not modeled yet: district/office assignment, Standard/Express queue
assignment, working-hours profiles (PRD 9.2 steps 3-5) - see Known Gaps.

## Security notes

- CORS is restricted to `FRONTEND_ORIGIN` (comma-separated list, defaults to `http://localhost:5173`) - requests from other origins are rejected.
- OTP-send, OTP-verify, citizen login, and staff login are all rate-limited (see `backend/app/Providers/AppServiceProvider.php`) to slow down SMS-bombing and brute-force attempts.

## Known gaps vs. the full PRD

Deliberately out of scope for this v1 slice - flag before assuming any of
this exists:

- All 6 event types now have real forms/validation, but every field list is
  still a **documented placeholder** - the PRD's actual field-list
  attachment (sections 5A-5F) never showed up. Reconcile against the real
  lists once someone tracks them down.
- No **dynamic workflow engine** (PRD section 10) - no configurable
  per-stage SLAs, no auto-escalation, no auto-advance-to-certificate safety
  net. Application status is a simplified linear model instead.
- No **admin configuration UI** for fees/tiers/workflow templates (PRD 9) -
  `event_type_config` is seeded with placeholder values, editable only by
  hand in the DB.
- No **NIA API integration** - always returns `UNAVAILABLE` (correctly,
  per the PRD's own design note, this doesn't block registration).
- No **real SMS gateway** - `channel: 'phone'` OTPs are always logged
  server-side and optionally echoed in dev responses, never actually
  texted. `channel: 'email'` OTPs *do* really send via Laravel Mail once
  SMTP credentials are configured (see Auth section above).
- No **real Npontu Pay integration** - mock mode only, no sandbox creds.
- 2FA (TOTP) is now implemented and mandatory for staff, but there's no
  recovery-code / backup-device flow if someone loses their authenticator.
- Certificate PDF generation + QR verification now exist (see Certificates
  section above), but there's no real PKI digital signature/BDR seal image
  (text-only placeholder), and field lists are the same documented
  placeholders as the rest of the app.
- Staff account provisioning now has a real API (see above), but district/
  office/queue/working-hours assignment (PRD 9.2 steps 3-5) still isn't
  modeled, and there's no frontend UI for any of this yet - API only.
- SLA "working days" math skips weekends only - no Ghana public holidays
  calendar (PRD 9.1.2) since there's no admin UI to configure one.

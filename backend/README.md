# HBDRP Backend (Laravel)

Laravel 13 API for the citizen self-service portal and back-office, per
`shared/api-contract.md`. Uses SQLite (Laravel's default) so there's no
database server to install for local dev.

This replaces the earlier Node/Express prototype, which lives on the
`feature/backend-scaffold` branch. The API contract (URLs, request/response
shapes) is identical - the frontend doesn't care which stack serves it.

## Setup

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --port 4000
```

Server runs on `http://localhost:4000`. Dev-only staff accounts are seeded
(`ADM-001`, `OFF-001`, `SUP-001`, `FIN-001`, password `changeme123`).

## Tests

```bash
php artisan test
```

## Requirements

- PHP >= 8.2, Composer

## Notes

- `DEV_EXPOSE_OTP=true` in `.env` echoes OTP codes in API responses since no
  SMS gateway is wired up yet - never enable this outside local dev. OTPs are
  also written to `storage/logs/laravel.log`.
- `NPONTU_PAY_MODE=mock` lets the full payment flow run without real Npontu
  Pay credentials - see `shared/api-contract.md` for the mock-confirm flow.
- See `shared/api-contract.md`'s "Known gaps" section for what's
  intentionally not built yet in this v1 slice.

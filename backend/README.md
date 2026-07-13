# HBDRP Backend

Node/Express API for the citizen self-service portal and back-office, per
`shared/api-contract.md`. Uses SQLite (`node:sqlite`, built into Node 22.5+)
so there's no database server to install for local dev.

## Setup

```bash
cd backend
cp .env.example .env
npm install
npm run dev
```

Server runs on `http://localhost:4000` (`PORT` in `.env`). The SQLite file
and schema are created automatically on first run at `backend/data/hbdrp.db`.

Dev-only staff accounts are seeded on startup and printed to the console
(`ADM-001`, `OFF-001`, `SUP-001`, `FIN-001`, password `changeme123`).

## Requirements

- Node.js >= 22.5 (uses the built-in `node:sqlite` module)

## Notes

- `DEV_EXPOSE_OTP=true` in `.env.example` echoes OTP codes in API responses
  since no SMS gateway is wired up yet - never enable this outside local dev.
- `NPONTU_PAY_MODE=mock` lets the full payment flow run without real Npontu
  Pay credentials - see `shared/api-contract.md` for the mock-confirm flow.
- See `shared/api-contract.md`'s "Known gaps" section for what's
  intentionally not built yet in this v1 slice.

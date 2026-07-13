import { nextSequence } from "../db/index.js";

// PRD 8.1 event codes.
const EVENT_CODES = {
  early_birth: "EB",
  late_birth: "LB",
  death: "DR",
  foetal_death: "FD",
  adoption: "AD",
  surrogacy: "SR",
};

export function newId(prefix) {
  return `${prefix}_${crypto.randomUUID()}`;
}

// PRD 8.1: BDR-{YYYY}-{EVENT_CODE}-{6-DIGIT-SEQUENCE}, sequence resets annually.
export function generateTrackingId(eventType) {
  const code = EVENT_CODES[eventType];
  if (!code) throw new Error(`Unknown event type: ${eventType}`);
  const year = new Date().getFullYear();
  const seq = nextSequence(`tracking:${year}:${eventType}`);
  return `BDR-${year}-${code}-${String(seq).padStart(6, "0")}`;
}

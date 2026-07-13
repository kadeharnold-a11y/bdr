// PRD 4.1: Ghana mobile numbers, MTN/Vodafone/AirtelTigo. Frontend sends the
// 9-digit local number without the +233 prefix or leading 0 (see SignUp.vue),
// so that's the canonical stored format here too.
export function isValidGhanaMobile(phone) {
  return typeof phone === "string" && /^\d{9}$/.test(phone);
}

// PRD 4.1: GHA-XXXXXXXXX-X format.
export function isValidGhanaCardNumber(cardNumber) {
  return typeof cardNumber === "string" && /^GHA-\d{9}-\d$/.test(cardNumber);
}

export function isValidPin(pin) {
  return typeof pin === "string" && /^\d{6}$/.test(pin);
}

export function isValidEmail(email) {
  return typeof email === "string" && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

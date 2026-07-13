// PRD sections 5A-5F ("Required Form Fields" / "Required Supporting
// Documents") all say "Pick from the Attached file shared" - that
// attachment was never actually included with the PRD. The field lists
// below are reasonable placeholders based on standard CRVS practice
// (matches OpenCRVS's default form shapes) and MUST be reconciled against
// the real field lists once someone tracks them down.
export const FORM_SCHEMAS = {
  early_birth: {
    requiredFields: [
      "childFullName",
      "childSex",
      "childDateOfBirth",
      "placeOfBirth",
      "motherFullName",
      "motherGhanaCardNumber",
      "informantFullName",
      "informantRelationshipToChild",
      "informantPhone",
    ],
    requiredDocuments: ["hospitalBirthNotification", "parentGhanaCardCopy"],
  },
  // PRD 5C: governed by Section 22-29 of Act 1027 (2020); must be
  // registered before a burial permit is issued (not enforced here - no
  // burial permit workflow exists yet).
  death: {
    requiredFields: [
      "deceasedFullName",
      "dateOfDeath",
      "placeOfDeath",
      "causeOfDeath",
      "informantFullName",
      "informantRelationshipToDeceased",
      "informantPhone",
    ],
    requiredDocuments: ["medicalCertificateOfCause", "deceasedIdCopy"],
  },
};

export function validateApplicationForm(eventType, formData) {
  const schema = FORM_SCHEMAS[eventType];
  if (!schema) return { ok: false, missingFields: [], error: "UNSUPPORTED_EVENT_TYPE" };

  const missingFields = schema.requiredFields.filter((field) => {
    const value = formData?.[field];
    return value === undefined || value === null || value === "";
  });

  return { ok: missingFields.length === 0, missingFields };
}

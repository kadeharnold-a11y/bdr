// PRD sections 5A.1/5A.2 ("Required Form Fields" / "Required Supporting
// Documents") both say "Pick from the Attached file shared" - that
// attachment was never actually included with the PRD. The field list below
// is a reasonable placeholder based on standard CRVS birth-registration
// practice (matches OpenCRVS's default birth form shape) and MUST be
// reconciled against the real field list once someone tracks it down.
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

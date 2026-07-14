<?php

// PRD sections 5A-5F ("Required Form Fields" / "Required Supporting
// Documents") all say "Pick from the Attached file shared" - that attachment
// was never actually included with the PRD. These field lists are reasonable
// placeholders based on standard CRVS practice (matches OpenCRVS's default
// form shapes) and MUST be reconciled against the real lists once someone
// tracks them down.
return [
    'early_birth' => [
        'required_fields' => [
            'childFullName',
            'childSex',
            'childDateOfBirth',
            'placeOfBirth',
            'motherFullName',
            'motherGhanaCardNumber',
            'informantFullName',
            'informantRelationshipToChild',
            'informantPhone',
        ],
        'required_documents' => ['hospitalBirthNotification', 'parentGhanaCardCopy'],
    ],
    // PRD 5C: governed by Section 22-29 of Act 1027 (2020); must be
    // registered before a burial permit is issued (not enforced here - no
    // burial permit workflow exists yet).
    'death' => [
        'required_fields' => [
            'deceasedFullName',
            'dateOfDeath',
            'placeOfDeath',
            'causeOfDeath',
            'informantFullName',
            'informantRelationshipToDeceased',
            'informantPhone',
        ],
        'required_documents' => ['medicalCertificateOfCause', 'deceasedIdCopy'],
    ],
    // PRD 5B: registration more than 12 months after birth, governed by
    // Section 14-17 of Act 1027 (2020) - requires proof of birth beyond the
    // standard hospital notification since none was filed at the time.
    'late_birth' => [
        'required_fields' => [
            'childFullName',
            'childSex',
            'childDateOfBirth',
            'placeOfBirth',
            'motherFullName',
            'fatherFullName',
            'reasonForLateRegistration',
            'informantFullName',
            'informantRelationshipToChild',
            'informantPhone',
        ],
        'required_documents' => ['swornDeclarationOfLateBirth', 'proofOfBirthRecord', 'parentGhanaCardCopy'],
    ],
    // PRD 5D: stillbirth at/after 28 weeks gestation, Section 30-33 of
    // Act 1027 (2020), usually initiated by the hospital/health facility.
    'foetal_death' => [
        'required_fields' => [
            'motherFullName',
            'motherGhanaCardNumber',
            'gestationalAgeWeeks',
            'dateOfFoetalDeath',
            'facilityName',
            'informantFullName',
            'informantPhone',
        ],
        'required_documents' => ['medicalCertificateFoetalDeath', 'motherIdCopy'],
    ],
    // PRD 5E: Children's Act 560 (1998) + Act 1027 (2020), requires a prior
    // court adoption order.
    'adoption' => [
        'required_fields' => [
            'childFullName',
            'childDateOfBirth',
            'adoptiveMotherFullName',
            'adoptiveFatherFullName',
            'courtOrderReference',
            'courtName',
            'informantFullName',
            'informantPhone',
        ],
        'required_documents' => ['courtAdoptionOrder', 'adoptiveParentGhanaCardCopy'],
    ],
    // PRD 5F: Section 22 of Act 1027 (2020) - intended parents are recorded
    // as the legal parents, surrogate is not named on the certificate.
    'surrogacy' => [
        'required_fields' => [
            'childFullName',
            'childDateOfBirth',
            'intendedMotherFullName',
            'intendedFatherFullName',
            'surrogacyAgreementReference',
            'facilityName',
            'informantFullName',
            'informantPhone',
        ],
        'required_documents' => ['surrogacyAgreementDocument', 'hospitalBirthNotification', 'intendedParentGhanaCardCopy'],
    ],
];

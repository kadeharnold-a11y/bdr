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
];

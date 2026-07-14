<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Support\CertificateBuilder;
use Illuminate\Http\JsonResponse;

class CertificateController extends Controller
{
    // PRD 12.1: public verification page - confirms registration type, event
    // date, registration date, and registered name, without exposing full
    // personal data (no phone/email/Ghana Card, no full form_data).
    public function verify(string $serial): JsonResponse
    {
        $certificate = Certificate::where('serial', $serial)->first();
        if (! $certificate) {
            return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'No certificate found for this serial']], 404);
        }

        $application = $certificate->application;
        $display = CertificateBuilder::displayFieldsFor($application);

        return response()->json([
            'serial' => $certificate->serial,
            'valid' => true,
            'registrationType' => $application->event_type,
            'registeredName' => $display['name'],
            'eventDate' => $display['eventDate'],
            'registrationDate' => $application->submitted_at?->toDateString(),
            'issuedAt' => $certificate->issued_at?->toISOString(),
        ]);
    }
}

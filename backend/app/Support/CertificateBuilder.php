<?php

namespace App\Support;

use App\Models\Application;
use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// PRD 12: certificates are digitally-sealed PDFs with an embedded QR code
// linking to a public verification page. No real PKI digital signature is
// applied (that needs an actual BDR-held signing key/HSM - out of scope for
// this v1 slice) - "sealed" here just means BDR branding + QR + serial.
class CertificateBuilder
{
    private const EVENT_CODES = [
        'early_birth' => 'EB',
        'late_birth' => 'LB',
        'death' => 'DR',
        'foetal_death' => 'FD',
        'adoption' => 'AD',
        'surrogacy' => 'SR',
    ];

    // PRD 12.2 templates: which form_data fields represent the "headline"
    // name and event date/place for each certificate type. Field names match
    // config/form_schemas.php's (also-placeholder) schemas.
    private const TEMPLATE_FIELDS = [
        'early_birth' => ['name' => 'childFullName', 'eventDateField' => 'childDateOfBirth', 'eventLabel' => 'Date of Birth', 'placeField' => 'placeOfBirth'],
        'late_birth' => ['name' => 'childFullName', 'eventDateField' => 'childDateOfBirth', 'eventLabel' => 'Date of Birth', 'placeField' => 'placeOfBirth', 'note' => 'Registered Late under Section 14 of Act 1027 (2020)'],
        'death' => ['name' => 'deceasedFullName', 'eventDateField' => 'dateOfDeath', 'eventLabel' => 'Date of Death', 'placeField' => 'placeOfDeath'],
        'foetal_death' => ['name' => 'motherFullName', 'eventDateField' => 'dateOfFoetalDeath', 'eventLabel' => 'Date of Foetal Death', 'placeField' => 'facilityName', 'note' => 'Foetal Death'],
        'adoption' => ['name' => 'childFullName', 'eventDateField' => 'childDateOfBirth', 'eventLabel' => 'Date of Birth', 'placeField' => null],
        'surrogacy' => ['name' => 'childFullName', 'eventDateField' => 'childDateOfBirth', 'eventLabel' => 'Date of Birth', 'placeField' => null],
    ];

    // PRD 12.1: what the public verification page is allowed to show -
    // registered name and event date, nothing else from form_data.
    public static function displayFieldsFor(Application $application): array
    {
        $template = self::TEMPLATE_FIELDS[$application->event_type];
        $formData = $application->form_data ?? [];

        return [
            'name' => $formData[$template['name']] ?? null,
            'eventDate' => $formData[$template['eventDateField']] ?? null,
        ];
    }

    public static function generateFor(Application $application): Certificate
    {
        $serial = self::nextSerial($application->event_type);
        $verifyUrl = url("/api/certificates/verify/{$serial}");

        $qr = (new Builder(data: $verifyUrl, size: 180, margin: 5))->build();
        $qrDataUri = 'data:'.$qr->getMimeType().';base64,'.base64_encode($qr->getString());

        $template = self::TEMPLATE_FIELDS[$application->event_type];
        $formData = $application->form_data ?? [];

        $pdf = Pdf::loadView('certificates.certificate', [
            'application' => $application,
            'serial' => $serial,
            'qrDataUri' => $qrDataUri,
            'name' => $formData[$template['name']] ?? 'N/A',
            'eventLabel' => $template['eventLabel'],
            'eventDate' => $formData[$template['eventDateField']] ?? 'N/A',
            'place' => $template['placeField'] ? ($formData[$template['placeField']] ?? 'N/A') : null,
            'note' => $template['note'] ?? null,
            'eventTypeLabel' => str_replace('_', ' ', ucwords($application->event_type, '_')),
        ]);

        $path = "certificates/{$serial}.pdf";
        Storage::put($path, $pdf->output());

        return Certificate::create([
            'application_id' => $application->id,
            'serial' => $serial,
            'pdf_path' => $path,
            'issued_at' => now(),
        ]);
    }

    // Separate sequence namespace from tracking IDs (PRD 8.1) even though
    // the format is parallel - a certificate serial and a tracking ID for
    // the same application are deliberately different numbers.
    private static function nextSerial(string $eventType): string
    {
        $code = self::EVENT_CODES[$eventType] ?? throw new \InvalidArgumentException("Unknown event type: {$eventType}");
        $year = now()->year;
        $key = "certificate:{$year}:{$eventType}";

        return DB::transaction(function () use ($code, $year, $key) {
            DB::statement(
                'INSERT INTO sequences (seq_key, value) VALUES (?, 1)
                 ON CONFLICT(seq_key) DO UPDATE SET value = value + 1',
                [$key]
            );
            $seq = DB::table('sequences')->where('seq_key', $key)->value('value');

            return sprintf('CERT-%d-%s-%06d', $year, $code, $seq);
        });
    }
}

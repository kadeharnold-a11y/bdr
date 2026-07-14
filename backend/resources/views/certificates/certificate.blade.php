<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; color: #1a2e22; margin: 0; padding: 0; }
    .border { border: 6px double #00693f; padding: 36px; margin: 20px; }
    .header { text-align: center; margin-bottom: 24px; }
    .header h1 { font-size: 20px; margin: 0; color: #00693f; }
    .header p { margin: 4px 0 0; font-size: 12px; color: #555; }
    .title { text-align: center; font-size: 22px; font-weight: bold; margin: 24px 0; text-transform: uppercase; letter-spacing: 1px; }
    .note { text-align: center; font-size: 12px; font-style: italic; color: #a15c00; margin-bottom: 16px; }
    table.details { width: 100%; margin: 24px 0; font-size: 14px; }
    table.details td { padding: 8px 4px; border-bottom: 1px solid #ddd; }
    table.details td.label { color: #666; width: 40%; }
    .footer { display: table; width: 100%; margin-top: 40px; }
    .footer .seal { display: table-cell; vertical-align: middle; text-align: center; width: 50%; }
    .footer .qr { display: table-cell; vertical-align: middle; text-align: center; width: 50%; }
    .footer .qr img { width: 120px; height: 120px; }
    .serial { text-align: center; font-size: 11px; color: #888; margin-top: 24px; }
</style>
</head>
<body>
<div class="border">
    <div class="header">
        <h1>Republic of Ghana</h1>
        <p>Harmonized Births and Deaths Registry Portal</p>
    </div>

    <div class="title">{{ $eventTypeLabel }} Certificate</div>
    @if($note)
        <div class="note">{{ $note }}</div>
    @endif

    <table class="details">
        <tr><td class="label">Name</td><td>{{ $name }}</td></tr>
        <tr><td class="label">{{ $eventLabel }}</td><td>{{ $eventDate }}</td></tr>
        @if($place)
            <tr><td class="label">Place</td><td>{{ $place }}</td></tr>
        @endif
        <tr><td class="label">Tracking ID</td><td>{{ $application->tracking_id }}</td></tr>
        <tr><td class="label">Date of Registration</td><td>{{ $application->submitted_at?->toFormattedDateString() }}</td></tr>
        <tr><td class="label">Certificate Serial</td><td>{{ $serial }}</td></tr>
    </table>

    <div class="footer">
        <div class="seal">
            <p>BDR Digital Seal</p>
        </div>
        <div class="qr">
            <img src="{{ $qrDataUri }}" alt="Verification QR code">
            <p>Scan to verify</p>
        </div>
    </div>

    <div class="serial">This is a digitally issued certificate. Verify at hbdrp.bdr.gov.gh using serial {{ $serial }}.</div>
</div>
</body>
</html>

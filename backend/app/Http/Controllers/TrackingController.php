<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\JsonResponse;

class TrackingController extends Controller
{
    // PRD 8.3: public tracking, no login required. Only exposes status/tier/
    // event type/dates and the citizen's first name - no other personal data.
    public function show(string $trackingId): JsonResponse
    {
        $application = Application::where('tracking_id', $trackingId)->first();
        if (! $application) {
            return response()->json(['error' => ['code' => 'NOT_FOUND', 'message' => 'No application found for this tracking ID']], 404);
        }

        $firstName = explode(' ', $application->citizen?->full_name ?? '')[0] ?: null;

        return response()->json([
            'trackingId' => $application->tracking_id,
            'status' => $application->status,
            'tier' => $application->tier,
            'eventType' => $application->event_type,
            'submittedAt' => $application->submitted_at?->toISOString(),
            'estimatedCompletionDate' => $application->sla_deadline?->toISOString(),
            'citizenFirstName' => $firstName,
        ]);
    }
}

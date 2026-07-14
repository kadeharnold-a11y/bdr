<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\EventTypeConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// PRD 9.1: admin-managed fee/duration/tier config, no code changes needed.
// Changes apply to new applications immediately; in-progress applications
// keep whatever fee_amount/tier they already locked in (PRD 9.1 note).
class AdminConfigController extends Controller
{
    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }

    public function index(): JsonResponse
    {
        return response()->json(EventTypeConfig::all()->map->toContract());
    }

    public function update(Request $request, string $eventType): JsonResponse
    {
        $config = EventTypeConfig::find($eventType);
        if (! $config) {
            return $this->error('NOT_FOUND', 'Unknown event type', 404);
        }

        $feeChanging = $request->has('standardFee') || $request->has('expressFee');
        // PRD 9.1.1: "Fee Change Reason" is a required audit-trail field.
        if ($feeChanging && ! $request->input('reason')) {
            return $this->error('REASON_REQUIRED', 'A reason is required when changing fees', 400);
        }

        $next = [
            'standard_fee' => $request->input('standardFee', $config->standard_fee),
            'express_fee' => $request->input('expressFee', $config->express_fee),
            'standard_duration_days' => $request->input('standardDurationDays', $config->standard_duration_days),
            'express_duration_days' => $request->input('expressDurationDays', $config->express_duration_days),
            'express_enabled' => $request->has('expressEnabled')
                ? (bool) $request->input('expressEnabled')
                : $config->express_enabled,
        ];

        // PRD 9.1.1/9.1.2 validation rules.
        if ($next['standard_fee'] <= 0) {
            return $this->error('INVALID_FEE', 'Standard fee must be greater than 0', 400);
        }
        if ($next['express_fee'] <= $next['standard_fee']) {
            return $this->error('INVALID_FEE', 'Express fee must be greater than the standard fee', 400);
        }
        if ($next['express_duration_days'] >= $next['standard_duration_days']) {
            return $this->error('INVALID_DURATION', 'Express duration must be less than standard duration', 400);
        }

        $before = $config->toContract();
        $config->update($next);

        AuditLog::record('staff', $request->user()->id, 'EVENT_TYPE_CONFIG_UPDATED', 'event_type_config', $eventType, [
            'before' => $before,
            'after' => $config->fresh()->toContract(),
            'reason' => $request->input('reason'),
        ]);

        return response()->json($config->fresh()->toContract());
    }
}

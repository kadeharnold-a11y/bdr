<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CitizenController extends Controller
{
    private function profile(Request $request): array
    {
        $citizen = $request->user();

        return [
            'id' => $citizen->id,
            'phone' => $citizen->phone,
            'email' => $citizen->email,
            'full_name' => $citizen->full_name,
            'ghana_card_number' => $citizen->ghana_card_number,
            'date_of_birth' => $citizen->date_of_birth?->toDateString(),
            'gender' => $citizen->gender,
            'nia_status' => $citizen->nia_status,
            'created_at' => $citizen->created_at?->toISOString(),
        ];
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->profile($request));
    }

    public function update(Request $request): JsonResponse
    {
        $request->user()->update(['email' => $request->input('email')]);

        return response()->json($this->profile($request));
    }

    // PRD 4.3: dashboard should let a citizen understand every application's
    // status within 5 seconds - active/draft/completed split done here rather
    // than left for the frontend to derive from a flat list.
    public function dashboard(Request $request): JsonResponse
    {
        $applications = $request->user()->applications()->orderByDesc('last_saved_at')->get();

        [$drafts, $rest] = $applications->partition(fn ($a) => in_array($a->status, ['DRAFT', 'PAYMENT_PENDING'], true));
        [$completed, $active] = $rest->partition(fn ($a) => in_array($a->status, ['COMPLETED', 'REJECTED'], true));

        $summarize = fn ($apps) => $apps->map(fn ($a) => [
            'id' => $a->id,
            'tracking_id' => $a->tracking_id,
            'event_type' => $a->event_type,
            'tier' => $a->tier,
            'status' => $a->status,
            'fee_amount' => $a->fee_amount,
            'fee_currency' => $a->fee_currency,
            'sla_deadline' => $a->sla_deadline?->toISOString(),
            'last_saved_at' => $a->last_saved_at?->toISOString(),
            'created_at' => $a->created_at?->toISOString(),
            'submitted_at' => $a->submitted_at?->toISOString(),
        ])->values();

        return response()->json([
            'activeApplications' => $summarize($active),
            'drafts' => $summarize($drafts),
            'completedApplications' => $summarize($completed),
            'notifications' => [], // TODO: notifications table not built yet in this v1 slice.
        ]);
    }
}

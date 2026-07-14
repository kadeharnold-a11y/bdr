<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\AuditLog;
use App\Models\StaffLoginChallenge;
use App\Models\StaffUser;
use App\Support\Notifier;
use App\Support\Tokens;
use App\Support\TwoFactor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    private const QUEUE_STATUSES = ['SUBMITTED', 'UNDER_REVIEW', 'CORRECTIONS_REQUIRED', 'AWAITING_APPROVAL'];

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }

    private function queueItem(Application $application): array
    {
        return [
            'id' => $application->id,
            'trackingId' => $application->tracking_id,
            'eventType' => $application->event_type,
            'tier' => $application->tier,
            'status' => $application->status,
            'assignedStaffId' => $application->assigned_staff_id,
            'slaDeadline' => $application->sla_deadline?->toISOString(),
            'slaPercentRemaining' => $this->slaPercentRemaining($application),
            'submittedAt' => $application->submitted_at?->toISOString(),
            'createdAt' => $application->created_at?->toISOString(),
        ];
    }

    private function slaPercentRemaining(Application $application): ?int
    {
        if (! $application->sla_deadline || ! $application->submitted_at) {
            return null;
        }
        $total = $application->submitted_at->diffInSeconds($application->sla_deadline, false);
        $remaining = now()->diffInSeconds($application->sla_deadline, false);
        if ($total <= 0) {
            return 0;
        }

        return max(0, (int) round($remaining / $total * 100));
    }

    // PRD 9.2 step 6: 2FA is mandatory for all back-office users. Password is
    // step 1; step 2 is always a TOTP code, generating+enrolling a secret on
    // first-ever login rather than requiring a separate setup flow.
    public function login(Request $request): JsonResponse
    {
        $staff = StaffUser::where('staff_id', $request->input('staffId'))->where('active', true)->first();
        if (! $staff || ! Hash::check((string) $request->input('password'), $staff->password)) {
            return $this->error('INVALID_CREDENTIALS', 'Staff ID or password is incorrect', 401);
        }

        $isNewSetup = ! $staff->hasTwoFactorEnabled();
        if ($isNewSetup) {
            // Secret is written now (not just held in-memory) so the pending
            // setup survives across requests; it only "counts" as enabled
            // once two_factor_confirmed_at is set in verifyTwoFactor().
            $staff->update(['two_factor_secret' => TwoFactor::generateSecret()]);
        }

        $challenge = StaffLoginChallenge::create([
            'token' => 'chl_'.Str::uuid(),
            'staff_user_id' => $staff->id,
            'is_new_setup' => $isNewSetup,
            'expires_at' => now()->addMinutes(10),
        ]);

        if ($isNewSetup) {
            return response()->json([
                'twoFactorSetupRequired' => true,
                'challengeToken' => $challenge->token,
                'secret' => $staff->two_factor_secret,
                'otpauthUrl' => TwoFactor::otpauthUrl($staff->staff_id, $staff->two_factor_secret),
            ]);
        }

        return response()->json([
            'twoFactorRequired' => true,
            'challengeToken' => $challenge->token,
        ]);
    }

    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $challenge = StaffLoginChallenge::find($request->input('challengeToken'));
        if (! $challenge || now()->greaterThan($challenge->expires_at)) {
            return $this->error('INVALID_CHALLENGE', 'Log in again', 400);
        }

        $staff = $challenge->staff;
        if (! TwoFactor::verify($staff->two_factor_secret, (string) $request->input('code'))) {
            return $this->error('INVALID_2FA_CODE', 'Incorrect verification code', 400);
        }

        if ($challenge->is_new_setup) {
            $staff->update(['two_factor_confirmed_at' => now()]);
        }
        $challenge->delete();

        return response()->json([
            'staffId' => $staff->staff_id,
            'role' => $staff->role,
            'fullName' => $staff->full_name,
            ...Tokens::forStaff($staff),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['loggedOut' => true]);
    }

    // PRD 11.1: dual-lane queue (Standard / Express), SLA-aware. The full
    // stage-by-stage workflow engine (PRD 10) isn't built - simplified
    // linear status model.
    public function queue(Request $request): JsonResponse
    {
        $query = Application::whereIn('status', self::QUEUE_STATUSES)
            ->orderByDesc('tier')
            ->orderBy('sla_deadline');

        if ($tier = $request->query('tier')) {
            $query->where('tier', $tier);
        }
        if ($request->query('mine') === 'true') {
            $query->where('assigned_staff_id', $request->user()->id);
        }

        return response()->json($query->get()->map(fn ($a) => $this->queueItem($a)));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $application = Application::find($id);
        if (! $application) {
            return $this->error('NOT_FOUND', 'Application not found', 404);
        }

        $citizen = $application->citizen;

        return response()->json([
            ...$this->queueItem($application),
            'formData' => $application->form_data ?: (object) [],
            'citizen' => [
                'id' => $citizen->id,
                'full_name' => $citizen->full_name,
                'phone' => $citizen->phone,
                'email' => $citizen->email,
                'ghana_card_number' => $citizen->ghana_card_number,
                'nia_status' => $citizen->nia_status,
            ],
            'documents' => $application->documents->map(fn ($d) => [
                'id' => $d->id,
                'field_name' => $d->field_name,
                'original_name' => $d->original_name,
                'mime_type' => $d->mime_type,
                'size_bytes' => $d->size_bytes,
                'created_at' => $d->created_at?->toISOString(),
            ]),
        ]);
    }

    // PRD 11.2: application workspace's document viewer needs the actual file.
    public function downloadDocument(string $id, string $documentId)
    {
        $application = Application::find($id);
        $document = $application?->documents()->where('id', $documentId)->first();
        if (! $document) {
            return $this->error('NOT_FOUND', 'Document not found', 404);
        }

        return Storage::download($document->stored_path, $document->original_name);
    }

    // Officer picks up an unassigned application (PRD 11.1 "My Queue").
    public function claim(Request $request, string $id): JsonResponse
    {
        $application = Application::find($id);
        if (! $application) {
            return $this->error('NOT_FOUND', 'Application not found', 404);
        }
        if ($application->status !== 'SUBMITTED') {
            return $this->error('NOT_CLAIMABLE', 'Only newly submitted applications can be claimed', 409);
        }

        $application->update(['status' => 'UNDER_REVIEW', 'assigned_staff_id' => $request->user()->id]);
        AuditLog::record('staff', $request->user()->id, 'APPLICATION_CLAIMED', 'application', $application->id);

        return response()->json($this->queueItem($application->fresh()));
    }

    // PRD 11.3: SLA clock "pauses" in the PRD; modeled by leaving
    // sla_deadline untouched while status is CORRECTIONS_REQUIRED (no
    // clock-pausing engine exists yet).
    public function requestCorrections(Request $request, string $id): JsonResponse
    {
        $application = Application::find($id);
        if (! $application) {
            return $this->error('NOT_FOUND', 'Application not found', 404);
        }
        if (! in_array($application->status, ['UNDER_REVIEW', 'AWAITING_APPROVAL'], true)) {
            return $this->error('INVALID_STATE', "Application isn't in review", 409);
        }

        $application->update(['status' => 'CORRECTIONS_REQUIRED']);
        AuditLog::record('staff', $request->user()->id, 'CORRECTIONS_REQUESTED', 'application', $application->id, [
            'fields' => $request->input('fields'),
            'notes' => $request->input('notes'),
        ]);
        Notifier::correctionsRequested($application);

        return response()->json(['status' => 'CORRECTIONS_REQUIRED']);
    }

    public function approve(Request $request, string $id): JsonResponse
    {
        $application = Application::find($id);
        if (! $application) {
            return $this->error('NOT_FOUND', 'Application not found', 404);
        }
        if (! in_array($application->status, ['UNDER_REVIEW', 'AWAITING_APPROVAL'], true)) {
            return $this->error('INVALID_STATE', "Application isn't in review", 409);
        }

        $application->update(['status' => 'APPROVED']);
        AuditLog::record('staff', $request->user()->id, 'APPLICATION_APPROVED', 'application', $application->id);
        Notifier::applicationApproved($application);

        return response()->json(['status' => 'APPROVED']);
    }

    // Stands in for PRD 10.2 stages 5-6 (certificate generation/print/
    // dispatch) collapsed into one step - no certificate PDF pipeline yet.
    public function complete(Request $request, string $id): JsonResponse
    {
        $application = Application::find($id);
        if (! $application) {
            return $this->error('NOT_FOUND', 'Application not found', 404);
        }
        if ($application->status !== 'APPROVED') {
            return $this->error('INVALID_STATE', 'Application must be approved first', 409);
        }

        $application->update(['status' => 'COMPLETED']);
        AuditLog::record('staff', $request->user()->id, 'APPLICATION_COMPLETED', 'application', $application->id);
        Notifier::certificateReady($application);

        return response()->json(['status' => 'COMPLETED']);
    }

    public function reject(Request $request, string $id): JsonResponse
    {
        $application = Application::find($id);
        if (! $application) {
            return $this->error('NOT_FOUND', 'Application not found', 404);
        }
        if (! $request->input('reason')) {
            return $this->error('REASON_REQUIRED', 'A rejection reason is required', 400);
        }

        $application->update(['status' => 'REJECTED']);
        AuditLog::record('staff', $request->user()->id, 'APPLICATION_REJECTED', 'application', $application->id, [
            'reason' => $request->input('reason'),
        ]);
        Notifier::applicationRejected($application, $request->input('reason'));

        return response()->json(['status' => 'REJECTED']);
    }
}

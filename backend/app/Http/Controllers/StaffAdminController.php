<?php

namespace App\Http\Controllers;

use App\Mail\StaffInviteMail;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\StaffUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

// PRD 9.2: back-office team setup. District/queue/working-hours assignment
// (steps 3-5) aren't modeled yet - only account provisioning, roles, and
// activation status (steps 1-2, 6-7). See shared/api-contract.md gaps.
class StaffAdminController extends Controller
{
    private const ROLES = ['ADMIN', 'REGISTRATION_OFFICER', 'SUPERVISOR', 'FINANCE_OFFICER'];

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }

    private function serialize(StaffUser $staff): array
    {
        return [
            'id' => $staff->id,
            'staffId' => $staff->staff_id,
            'fullName' => $staff->full_name,
            'email' => $staff->email,
            'role' => $staff->role,
            'active' => (bool) $staff->active,
            'createdAt' => $staff->created_at?->toISOString(),
        ];
    }

    public function index(): JsonResponse
    {
        return response()->json(StaffUser::orderBy('created_at')->get()->map(fn ($s) => $this->serialize($s)));
    }

    // PRD 9.2 step 6: "User receives email invitation with temporary
    // password." Sends via Laravel Mail (real if SMTP is configured, logged
    // otherwise - see shared/api-contract.md).
    public function store(Request $request): JsonResponse
    {
        $staffId = $request->input('staffId');
        $fullName = $request->input('fullName');
        $role = $request->input('role');
        $email = $request->input('email');

        if (! $staffId || ! $fullName) {
            return $this->error('MISSING_FIELDS', 'staffId and fullName are required', 400);
        }
        if (! in_array($role, self::ROLES, true)) {
            return $this->error('INVALID_ROLE', 'role must be one of: '.implode(', ', self::ROLES), 400);
        }
        if (StaffUser::where('staff_id', $staffId)->exists()) {
            return $this->error('STAFF_ID_TAKEN', 'That staff ID is already in use', 409);
        }
        if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('INVALID_EMAIL', 'Enter a valid email address', 400);
        }

        $temporaryPassword = Str::password(12, symbols: false);

        $staff = StaffUser::create([
            'id' => (string) Str::uuid(),
            'staff_id' => $staffId,
            'full_name' => $fullName,
            'email' => $email,
            'role' => $role,
            'password' => Hash::make($temporaryPassword),
            'active' => true,
        ]);

        if ($email) {
            Mail::to($email)->send(new StaffInviteMail($staffId, $fullName, $role, $temporaryPassword));
        }

        AuditLog::record('staff', request()->user()?->id, 'STAFF_USER_CREATED', 'staff_user', $staff->id, ['role' => $role]);

        return response()->json([
            ...$this->serialize($staff),
            // Only ever returned here, at creation time - never retrievable again.
            'temporaryPassword' => $temporaryPassword,
        ], 201);
    }

    // PRD 9.2 step 7: deactivate/suspend/modify role at any time; a
    // deactivated user's in-progress applications get reassigned. The PRD
    // says "to their supervisor queue" - since there's no per-officer
    // supervisor hierarchy modeled, this reassigns to any active SUPERVISOR.
    public function update(Request $request, string $id): JsonResponse
    {
        $staff = StaffUser::find($id);
        if (! $staff) {
            return $this->error('NOT_FOUND', 'Staff user not found', 404);
        }

        if ($request->has('role') && ! in_array($request->input('role'), self::ROLES, true)) {
            return $this->error('INVALID_ROLE', 'role must be one of: '.implode(', ', self::ROLES), 400);
        }

        $wasActive = (bool) $staff->active;
        $staff->update([
            'full_name' => $request->input('fullName', $staff->full_name),
            'role' => $request->input('role', $staff->role),
            'active' => $request->has('active') ? (bool) $request->input('active') : $staff->active,
        ]);

        if ($wasActive && ! $staff->active) {
            $supervisor = StaffUser::where('role', 'SUPERVISOR')->where('active', true)->where('id', '!=', $staff->id)->first();
            $reassigned = Application::where('assigned_staff_id', $staff->id)
                ->whereIn('status', ['UNDER_REVIEW', 'CORRECTIONS_REQUIRED', 'AWAITING_APPROVAL'])
                ->update(['assigned_staff_id' => $supervisor?->id]);

            AuditLog::record('staff', request()->user()?->id, 'STAFF_USER_DEACTIVATED', 'staff_user', $staff->id, [
                'reassignedApplications' => $reassigned,
                'reassignedTo' => $supervisor?->id,
            ]);
        }

        return response()->json($this->serialize($staff->fresh()));
    }
}

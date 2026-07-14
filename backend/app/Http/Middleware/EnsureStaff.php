<?php

namespace App\Http\Middleware;

use App\Models\StaffUser;
use Closure;
use Illuminate\Http\Request;

// Runs after auth:sanctum. Usage: staff (any role) or staff:ADMIN,SUPERVISOR
// to restrict to specific roles - mirrors the Express requireStaffAuth().
class EnsureStaff
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();
        if (! $user instanceof StaffUser || ! $user->tokenCan('staff:'.$user->role)) {
            return response()->json(['error' => ['code' => 'INVALID_TOKEN', 'message' => 'Invalid or expired access token']], 401);
        }
        if ($roles && ! in_array($user->role, $roles, true)) {
            return response()->json(['error' => ['code' => 'FORBIDDEN', 'message' => 'Role not permitted for this action']], 403);
        }

        return $next($request);
    }
}

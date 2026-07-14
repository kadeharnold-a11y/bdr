<?php

namespace App\Http\Middleware;

use App\Models\Citizen;
use Closure;
use Illuminate\Http\Request;

// Runs after auth:sanctum. Rejects staff tokens (and citizen refresh tokens)
// on citizen-only routes.
class EnsureCitizen
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user instanceof Citizen || ! $user->tokenCan('citizen')) {
            return response()->json(['error' => ['code' => 'INVALID_TOKEN', 'message' => 'Invalid or expired access token']], 401);
        }

        return $next($request);
    }
}

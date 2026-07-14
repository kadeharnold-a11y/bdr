<?php

namespace App\Support;

use App\Models\Citizen;
use App\Models\StaffUser;

// Sanctum personal access tokens instead of the Express prototype's JWTs.
// The contract (shared/api-contract.md) only promises opaque bearer strings
// plus expiresIn, so the swap is invisible to the frontend.
class Tokens
{
    /** @return array{accessToken: string, refreshToken: string, expiresIn: int} */
    public static function forCitizen(Citizen $citizen): array
    {
        $accessTtl = (int) config('hbdrp.access_token_ttl');
        $refreshTtl = (int) config('hbdrp.refresh_token_ttl');

        return [
            'accessToken' => $citizen->createToken('access', ['citizen'], now()->addSeconds($accessTtl))->plainTextToken,
            'refreshToken' => $citizen->createToken('refresh', ['refresh'], now()->addSeconds($refreshTtl))->plainTextToken,
            'expiresIn' => $accessTtl,
        ];
    }

    /** @return array{accessToken: string, expiresIn: int} */
    public static function forStaff(StaffUser $staff): array
    {
        $accessTtl = (int) config('hbdrp.access_token_ttl');

        return [
            'accessToken' => $staff->createToken('access', ['staff:'.$staff->role], now()->addSeconds($accessTtl))->plainTextToken,
            'expiresIn' => $accessTtl,
        ];
    }
}

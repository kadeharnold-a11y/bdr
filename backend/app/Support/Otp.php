<?php

namespace App\Support;

use App\Models\AuthSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Otp
{
    public const TTL_SECONDS = 600; // PRD 4.1: OTP valid for 10 minutes.

    // Creates an auth_sessions row and "sends" the OTP. There's no SMS
    // gateway wired up yet (PRD 13 names Hubtel "or equivalent" but no creds
    // exist - PRD 15.2 assumptions), so in dev we log it and optionally echo
    // it back in the API response via DEV_EXPOSE_OTP.
    public static function start(string $purpose, string $phone, ?string $citizenId = null, ?array $profile = null): AuthSession
    {
        $session = AuthSession::create([
            'token' => ($purpose === 'register' ? 'reg_' : 'log_').Str::uuid(),
            'purpose' => $purpose,
            'phone' => $phone,
            'citizen_id' => $citizenId,
            'otp_code' => (string) random_int(100000, 999999),
            'otp_expires_at' => now()->addSeconds(self::TTL_SECONDS),
            'otp_verified' => false,
            'profile' => $profile,
        ]);

        Log::info("[OTP][{$purpose}] phone={$phone} code={$session->otp_code} (expires in ".self::TTL_SECONDS.'s)');

        return $session;
    }

    /** @return array{ok: bool, error?: string, session?: AuthSession} */
    public static function verify(?string $token, ?string $code): array
    {
        $session = $token ? AuthSession::find($token) : null;
        if (! $session) {
            return ['ok' => false, 'error' => 'SESSION_NOT_FOUND'];
        }
        if (now()->greaterThan($session->otp_expires_at)) {
            return ['ok' => false, 'error' => 'OTP_EXPIRED'];
        }
        if ($session->otp_code !== (string) $code) {
            return ['ok' => false, 'error' => 'OTP_INCORRECT'];
        }

        $session->update(['otp_verified' => true]);

        return ['ok' => true, 'session' => $session];
    }

    public static function expose(): bool
    {
        return (bool) config('hbdrp.dev_expose_otp');
    }
}

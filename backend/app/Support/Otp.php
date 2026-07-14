<?php

namespace App\Support;

use App\Mail\OtpMail;
use App\Models\AuthSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class Otp
{
    public const TTL_SECONDS = 600; // PRD 4.1: OTP valid for 10 minutes.

    // Creates an auth_sessions row and dispatches the OTP over the chosen
    // channel. Phone has no SMS gateway wired up yet (PRD 13 names Hubtel
    // "or equivalent" but no creds exist - PRD 15.2 assumptions), so phone
    // codes are only ever logged. Email actually sends via Laravel Mail -
    // if MAIL_MAILER isn't configured with real credentials it falls back to
    // Laravel's "log" driver, which just writes it to the log instead of
    // erroring, same as the phone path.
    public static function start(
        string $purpose,
        string $phone,
        ?string $citizenId = null,
        ?array $profile = null,
        string $channel = 'phone',
        ?string $email = null,
    ): AuthSession {
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

        if ($channel === 'email' && $email) {
            Mail::to($email)->send(new OtpMail($session->otp_code, $purpose));
            Log::info("[OTP][{$purpose}][email] to={$email} code={$session->otp_code} (expires in ".self::TTL_SECONDS.'s)');
        } else {
            Log::info("[OTP][{$purpose}][phone] phone={$phone} code={$session->otp_code} (expires in ".self::TTL_SECONDS.'s)');
        }

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

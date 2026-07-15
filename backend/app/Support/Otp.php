<?php

namespace App\Support;

use App\Exceptions\OtpDeliveryException;
use App\Mail\OtpMail;
use App\Models\AuthSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class Otp
{
    public static function ttlSeconds(): int
    {
        return (int) config('hbdrp.otp_ttl_seconds', 600);
    }

    /**
     * Issue a fresh OTP delivered to the user's chosen contact channel.
     *
     * @throws OtpDeliveryException
     */
    public static function start(
        string $purpose,
        string $phone,
        ?string $citizenId = null,
        ?array $profile = null,
        string $channel = 'phone',
        ?string $email = null,
    ): AuthSession {
        $deliveryTarget = $channel === 'email' ? (string) $email : $phone;
        $code = (string) random_int(100000, 999999);
        $ttl = self::ttlSeconds();

        return DB::transaction(function () use ($purpose, $phone, $citizenId, $profile, $channel, $deliveryTarget, $code, $ttl) {
            // Only one active OTP per phone + flow at a time.
            AuthSession::query()
                ->where('phone', $phone)
                ->where('purpose', $purpose)
                ->where('otp_verified', false)
                ->delete();

            $session = AuthSession::create([
                'token' => ($purpose === 'register' ? 'reg_' : 'log_').Str::uuid(),
                'purpose' => $purpose,
                'phone' => $phone,
                'citizen_id' => $citizenId,
                'otp_channel' => $channel,
                'delivery_target' => $deliveryTarget,
                'otp_code' => Hash::make($code),
                'otp_expires_at' => now()->addSeconds($ttl),
                'otp_verified' => false,
                'profile' => $profile,
            ]);

            try {
                if ($channel === 'email') {
                    self::deliverEmail($deliveryTarget, $code, $purpose, $ttl);
                    Log::info("[OTP][{$purpose}][email] sent", ['to' => $deliveryTarget, 'ttl' => $ttl]);
                } else {
                    self::deliverSms($deliveryTarget, $code, $purpose, $ttl);
                    Log::info("[OTP][{$purpose}][phone] sent", ['phone' => $deliveryTarget, 'ttl' => $ttl]);
                }
            } catch (OtpDeliveryException $e) {
                $session->delete();
                throw $e;
            }

            return $session;
        });
    }

    /** @throws OtpDeliveryException */
    private static function deliverEmail(string $email, string $code, string $purpose, int $ttlSeconds): void
    {
        if (! DeliveryConfig::emailReady()) {
            $issue = DeliveryConfig::emailIssues()[0] ?? 'Email delivery is not configured.';
            throw new OtpDeliveryException(
                $issue.' Update your .env file and run: php artisan config:clear',
                'EMAIL_NOT_CONFIGURED',
            );
        }

        try {
            Mail::to($email)->send(new OtpMail($code, $purpose, $ttlSeconds));
        } catch (\Throwable $e) {
            Log::error('[OTP][email] delivery failed', [
                'to' => $email,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);
            throw new OtpDeliveryException(
                'Could not send verification email: '.$e->getMessage(),
                'EMAIL_DELIVERY_FAILED',
            );
        }
    }

    /** @throws OtpDeliveryException */
    private static function deliverSms(string $phone, string $code, string $purpose, int $ttlSeconds): void
    {
        $minutes = (int) ceil($ttlSeconds / 60);
        $label = $purpose === 'login' ? 'sign-in' : 'registration';
        $message = "Your HBDRP {$label} code is {$code}. Valid for {$minutes} minutes. Do not share this code.";

        SmsGateway::send($phone, $message);
    }

    /** @return array{ok: bool, error?: string, session?: AuthSession} */
    public static function verify(?string $token, ?string $code): array
    {
        if (! is_string($code) || ! preg_match('/^\d{6}$/', $code)) {
            return ['ok' => false, 'error' => 'INVALID_OTP_FORMAT'];
        }

        $session = $token ? AuthSession::find($token) : null;
        if (! $session) {
            return ['ok' => false, 'error' => 'SESSION_NOT_FOUND'];
        }
        if ($session->otp_used_at !== null || ($session->otp_verified && empty($session->otp_code))) {
            return ['ok' => false, 'error' => 'OTP_ALREADY_USED'];
        }
        if (now()->greaterThan($session->otp_expires_at)) {
            return ['ok' => false, 'error' => 'OTP_EXPIRED'];
        }
        if (! Hash::check($code, $session->otp_code)) {
            return ['ok' => false, 'error' => 'OTP_INCORRECT'];
        }

        $session->update([
            'otp_verified' => true,
            'otp_used_at' => now(),
            'otp_code' => '', // invalidate — code cannot be reused
        ]);

        return ['ok' => true, 'session' => $session];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AuthSession;
use App\Models\Citizen;
use App\Support\Otp;
use App\Support\Tokens;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    private const PHONE_REGEX = '/^\d{9}$/';       // PRD 4.1: 9-digit local number, no +233/leading 0.
    private const GHANA_CARD_REGEX = '/^GHA-\d{9}-\d$/';
    private const PIN_REGEX = '/^\d{6}$/';

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }

    private function withDevOtp(array $payload, AuthSession $session): array
    {
        return Otp::expose() ? [...$payload, 'devOtp' => $session->otp_code] : $payload;
    }

    // --- Registration (PRD 4.1) -----------------------------------------

    public function registerSendOtp(Request $request): JsonResponse
    {
        $phone = $request->input('phone');
        $email = $request->input('email');

        if (! is_string($phone) || ! preg_match(self::PHONE_REGEX, $phone)) {
            return $this->error('INVALID_PHONE', 'Enter a valid 9-digit Ghana mobile number', 400);
        }
        if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('INVALID_EMAIL', 'Enter a valid email address', 400);
        }
        if (Citizen::where('phone', $phone)->exists()) {
            return $this->error('PHONE_ALREADY_REGISTERED', 'This phone number already has an account. Please log in instead.', 409);
        }

        $session = Otp::start('register', $phone, profile: ['email' => $email]);

        return response()->json($this->withDevOtp([
            'registrationToken' => $session->token,
            'otpSentTo' => $phone,
            'otpExpiresInSeconds' => Otp::TTL_SECONDS,
        ], $session));
    }

    public function registerVerifyOtp(Request $request): JsonResponse
    {
        $result = Otp::verify($request->input('registrationToken'), $request->input('otp'));
        if (! $result['ok']) {
            return $this->error($result['error'], 'OTP verification failed', 400);
        }

        return response()->json(['profileToken' => $result['session']->token]);
    }

    // PRD design note (4.1): never let NIA availability block registration -
    // if the API is down or the card isn't in NIA yet (common for newborns),
    // accept and flag for manual confirmation at the first in-person visit.
    public function registerProfile(Request $request): JsonResponse
    {
        $session = $this->verifiedRegisterSession($request->input('profileToken'));
        if (! $session) {
            return $this->error('INVALID_SESSION', 'Start registration again', 400);
        }

        $fullName = $request->input('fullName');
        $ghanaCard = $request->input('ghanaCardNumber');

        if (! is_string($fullName) || strlen(trim($fullName)) < 2) {
            return $this->error('INVALID_NAME', 'Enter the full name as shown on the Ghana Card', 400);
        }
        if ($ghanaCard !== null && ! preg_match(self::GHANA_CARD_REGEX, $ghanaCard)) {
            return $this->error('INVALID_GHANA_CARD', 'Ghana Card number must be in GHA-XXXXXXXXX-X format', 400);
        }

        // TODO: call the real NIA API. No staging credentials exist yet
        // (PRD 15.2 assumption) so this always returns UNAVAILABLE for now.
        $nia = ['status' => 'UNAVAILABLE', 'dateOfBirth' => null, 'gender' => null];

        $session->update([
            'profile' => [
                ...$session->profile ?? [],
                'fullName' => trim($fullName),
                'ghanaCardNumber' => $ghanaCard,
                'email' => $request->input('email') ?? ($session->profile['email'] ?? null),
                'niaStatus' => $nia['status'],
            ],
        ]);

        return response()->json(['profileToken' => $session->token, 'nia' => $nia]);
    }

    public function registerPin(Request $request): JsonResponse
    {
        $session = $this->verifiedRegisterSession($request->input('profileToken'));
        if (! $session) {
            return $this->error('INVALID_SESSION', 'Start registration again', 400);
        }

        $pin = $request->input('pin');
        if (! is_string($pin) || ! preg_match(self::PIN_REGEX, $pin)) {
            return $this->error('INVALID_PIN', 'PIN must be exactly 6 digits', 400);
        }

        $profile = $session->profile ?? [];
        if (empty($profile['fullName'])) {
            return $this->error('PROFILE_INCOMPLETE', 'Complete the profile step before setting a PIN', 400);
        }

        $citizen = Citizen::create([
            'phone' => $session->phone,
            'email' => $profile['email'] ?? null,
            'full_name' => $profile['fullName'],
            'ghana_card_number' => $profile['ghanaCardNumber'] ?? null,
            'pin_hash' => Hash::make($pin),
            'nia_status' => $profile['niaStatus'] ?? 'UNVERIFIED',
        ]);

        $session->delete();

        return response()->json(['citizenId' => $citizen->id, ...Tokens::forCitizen($citizen)], 201);
    }

    // --- Login (PRD 4.2: phone + PIN, then OTP) --------------------------

    public function loginSendOtp(Request $request): JsonResponse
    {
        $phone = $request->input('phone');
        $pin = $request->input('pin');

        if (! is_string($phone) || ! preg_match(self::PHONE_REGEX, $phone)
            || ! is_string($pin) || ! preg_match(self::PIN_REGEX, $pin)) {
            return $this->error('INVALID_CREDENTIALS', 'Enter your phone number and 6-digit PIN', 400);
        }

        $citizen = Citizen::where('phone', $phone)->first();
        if (! $citizen || ! Hash::check($pin, $citizen->pin_hash)) {
            return $this->error('INVALID_CREDENTIALS', 'Phone number or PIN is incorrect', 401);
        }

        $session = Otp::start('login', $phone, citizenId: $citizen->id);

        return response()->json($this->withDevOtp([
            'loginToken' => $session->token,
            'otpSentTo' => $phone,
            'otpExpiresInSeconds' => Otp::TTL_SECONDS,
        ], $session));
    }

    public function loginVerifyOtp(Request $request): JsonResponse
    {
        $result = Otp::verify($request->input('loginToken'), $request->input('otp'));
        if (! $result['ok']) {
            return $this->error($result['error'], 'OTP verification failed', 400);
        }

        $citizen = Citizen::find($result['session']->citizen_id);
        $result['session']->delete();

        return response()->json(['citizenId' => $citizen->id, ...Tokens::forCitizen($citizen)]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $token = PersonalAccessToken::findToken((string) $request->input('refreshToken'));
        $citizen = $token?->tokenable;

        if (! $token || ! $citizen instanceof Citizen || ! $token->can('refresh')
            || ($token->expires_at && now()->greaterThan($token->expires_at))) {
            return $this->error('INVALID_REFRESH_TOKEN', 'Log in again', 401);
        }

        return response()->json(Tokens::forCitizen($citizen));
    }

    private function verifiedRegisterSession(?string $token): ?AuthSession
    {
        $session = $token ? AuthSession::find($token) : null;

        return ($session && $session->purpose === 'register' && $session->otp_verified) ? $session : null;
    }
}

<?php

namespace App\Support;

use PragmaRX\Google2FA\Google2FA;

class TwoFactor
{
    private static function engine(): Google2FA
    {
        return new Google2FA();
    }

    public static function generateSecret(): string
    {
        return self::engine()->generateSecretKey();
    }

    public static function otpauthUrl(string $label, string $secret): string
    {
        return self::engine()->getQRCodeUrl('HBDRP', $label, $secret);
    }

    public static function verify(string $secret, string $code): bool
    {
        return self::engine()->verifyKey($secret, $code);
    }
}

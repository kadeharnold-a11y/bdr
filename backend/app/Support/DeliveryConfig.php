<?php

namespace App\Support;

class DeliveryConfig
{
    /** @return list<string> */
    public static function smsIssues(): array
    {
        $issues = [];

        if (config('sms.driver') !== 'hubtel') {
            $issues[] = 'SMS_DRIVER must be "hubtel" for production SMS delivery.';

            return $issues;
        }

        if (! self::filled('sms.hubtel.client_id')) {
            $issues[] = 'HUBTEL_CLIENT_ID is missing or empty.';
        }
        if (! self::filled('sms.hubtel.client_secret')) {
            $issues[] = 'HUBTEL_CLIENT_SECRET is missing or empty.';
        }
        if (! self::filled('sms.hubtel.sender_id')) {
            $issues[] = 'HUBTEL_SENDER_ID is missing or empty.';
        }

        return $issues;
    }

    /** @return list<string> */
    public static function emailIssues(): array
    {
        $issues = [];
        $mailer = config('mail.default');

        // PHPUnit uses the array mailer with Mail::fake() — no SMTP creds needed.
        if (app()->runningUnitTests() && $mailer === 'array') {
            return [];
        }

        if ($mailer === 'log') {
            $issues[] = 'MAIL_MAILER is "log" — emails are not sent to real inboxes. Set MAIL_MAILER=smtp.';

            return $issues;
        }

        if ($mailer !== 'smtp') {
            $issues[] = "MAIL_MAILER is \"{$mailer}\" — only smtp is supported for OTP email delivery.";

            return $issues;
        }

        if (! self::filled('mail.mailers.smtp.host')) {
            $issues[] = 'MAIL_HOST is missing or empty.';
        }
        if (! self::filled('mail.mailers.smtp.username')) {
            $issues[] = 'MAIL_USERNAME is missing or empty.';
        }
        if (! self::filled('mail.mailers.smtp.password')) {
            $issues[] = 'MAIL_PASSWORD is missing or empty.';
        }
        if (! self::filled('mail.from.address')) {
            $issues[] = 'MAIL_FROM_ADDRESS is missing or empty.';
        }

        return $issues;
    }

    public static function smsReady(): bool
    {
        return self::smsIssues() === [];
    }

    public static function emailReady(): bool
    {
        return self::emailIssues() === [];
    }

    /** @return array{ready: bool, driver: string, issues: list<string>} */
    public static function smsStatus(): array
    {
        return [
            'ready' => self::smsReady(),
            'driver' => (string) config('sms.driver'),
            'issues' => self::smsIssues(),
        ];
    }

    /** @return array{ready: bool, mailer: string, issues: list<string>} */
    public static function emailStatus(): array
    {
        return [
            'ready' => self::emailReady(),
            'mailer' => (string) config('mail.default'),
            'issues' => self::emailIssues(),
        ];
    }

    private static function filled(string $key): bool
    {
        $value = config($key);

        return is_string($value) && trim($value) !== '';
    }
}

<?php

namespace App\Console\Commands;

use App\Mail\OtpMail;
use App\Support\DeliveryConfig;
use App\Support\SmsGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class VerifyOtpDelivery extends Command
{
    protected $signature = 'otp:verify-config
                            {--test-sms= : Send a test SMS OTP to this 9-digit Ghana number}
                            {--test-email= : Send a test email OTP to this address}';

    protected $description = 'Verify Hubtel SMS and SMTP email configuration for OTP delivery';

    public function handle(): int
    {
        $this->info('HBDRP OTP delivery configuration');
        $this->newLine();

        $this->renderChannel('SMS (Hubtel)', DeliveryConfig::smsStatus());
        $this->renderChannel('Email (SMTP)', DeliveryConfig::emailStatus());

        $smsPhone = $this->option('test-sms');
        $email = $this->option('test-email');

        if ($smsPhone) {
            $this->newLine();
            $this->testSms($smsPhone);
        }

        if ($email) {
            $this->newLine();
            $this->testEmail($email);
        }

        if (! DeliveryConfig::smsReady() || ! DeliveryConfig::emailReady()) {
            $this->newLine();
            $this->warn('Fix the issues above in backend/.env, then run: php artisan config:clear');

            return self::FAILURE;
        }

        if (! $smsPhone && ! $email) {
            $this->newLine();
            $this->comment('Config looks good. Run with --test-sms=244000000 or --test-email=you@example.com to send a live test.');
        }

        return self::SUCCESS;
    }

    /** @param array{ready: bool, driver?: string, mailer?: string, issues: list<string>} $status */
    private function renderChannel(string $label, array $status): void
    {
        $driver = $status['driver'] ?? $status['mailer'] ?? 'unknown';
        $this->line("<fg=cyan>{$label}</> ({$driver})");

        if ($status['ready']) {
            $this->line('  <fg=green>✓ Ready</>');
        } else {
            $this->line('  <fg=red>✗ Not configured</>');
            foreach ($status['issues'] as $issue) {
                $this->line("    • {$issue}");
            }
        }
    }

    private function testSms(string $phone): void
    {
        if (! preg_match('/^\d{9}$/', $phone)) {
            $this->error('Test SMS phone must be a 9-digit Ghana number (e.g. 244000000).');

            return;
        }

        if (! DeliveryConfig::smsReady()) {
            $this->error('SMS is not configured — fix Hubtel credentials first.');

            return;
        }

        $code = (string) random_int(100000, 999999);
        $this->info("Sending test SMS to +233{$phone}…");

        try {
            SmsGateway::send($phone, "HBDRP test OTP: {$code}. If you received this, SMS delivery is working.");
            $this->line('<fg=green>✓ SMS accepted by Hubtel. Check the phone for the message.</>');
        } catch (\Throwable $e) {
            $this->error('SMS test failed: '.$e->getMessage());
        }
    }

    private function testEmail(string $email): void
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Provide a valid --test-email address.');

            return;
        }

        if (! DeliveryConfig::emailReady()) {
            $this->error('Email is not configured — fix SMTP credentials first.');

            return;
        }

        $code = (string) random_int(100000, 999999);
        $this->info("Sending test email to {$email}…");

        try {
            Mail::to($email)->send(new OtpMail($code, 'register', 600));
            $this->line('<fg=green>✓ Email sent. Check the inbox (and spam folder).</>');
        } catch (\Throwable $e) {
            $this->error('Email test failed: '.$e->getMessage());
        }
    }
}

<?php

namespace Tests;

use App\Mail\OtpMail;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'smsc.hubtel.com/*' => Http::response(['Status' => 0, 'MessageId' => 'test-msg'], 200),
        ]);
    }

    // The auth manager caches the resolved guard/user across requests made
    // within a single test, so a request authenticated as a citizen would
    // "leak" into a following request that presents a staff bearer token.
    // Forget guards before every simulated request so each one authenticates
    // fresh from its own Authorization header, like real HTTP would.
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $this->app['auth']->forgetGuards();

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    protected function otpFromLastSms(): string
    {
        $hubtelRequests = collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), 'hubtel'))
            ->values();

        $request = $hubtelRequests->last()[0] ?? null;
        $this->assertNotNull($request, 'Expected an SMS OTP to be sent via Hubtel.');

        $content = $request->data()['Content'] ?? '';
        if (preg_match('/\b(\d{6})\b/', $content, $matches)) {
            return $matches[1];
        }

        $this->fail('No 6-digit OTP found in the last Hubtel SMS request.');
    }

    protected function otpFromLastEmail(): string
    {
        $mail = Mail::sent(OtpMail::class)->last();
        $this->assertNotNull($mail, 'Expected an OTP email to be sent.');

        return $mail->code;
    }
}

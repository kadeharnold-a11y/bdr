<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Support\DeliveryConfig;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Keyed by phone/token where possible (not just IP) so a single
        // number can't be SMS-bombed from many IPs. Limits mirror the
        // Express prototype: 5 sends / 10 verifies-or-logins per 15 min.
        RateLimiter::for('otp-send', fn (Request $request) => Limit::perMinutes(15, 5)
            ->by($request->input('phone') ?: $request->ip()));

        RateLimiter::for('otp-verify', fn (Request $request) => Limit::perMinutes(15, 10)
            ->by($request->input('registrationToken') ?: $request->input('loginToken') ?: $request->ip()));

        RateLimiter::for('login', fn (Request $request) => Limit::perMinutes(15, 10)
            ->by($request->input('phone') ?: $request->ip()));

        RateLimiter::for('staff-login', fn (Request $request) => Limit::perMinutes(15, 10)
            ->by($request->input('staffId') ?: $request->ip()));

        if (! $this->app->runningUnitTests()) {
            foreach (DeliveryConfig::smsIssues() as $issue) {
                Log::warning('[OTP][sms] '.$issue);
            }
            foreach (DeliveryConfig::emailIssues() as $issue) {
                Log::warning('[OTP][email] '.$issue);
            }
        }
    }
}

<?php

return [
    // Dev convenience: when true, OTP codes are echoed in API responses since
    // no SMS gateway (Hubtel etc, PRD 13) is wired up yet. Must be false in
    // any shared/prod env.
    'dev_expose_otp' => env('DEV_EXPOSE_OTP', false),

    // Npontu Pay (sandbox creds not yet provided - PRD 15.2 assumptions).
    // Payments run in mock mode until real credentials exist.
    'npontu_pay_mode' => env('NPONTU_PAY_MODE', 'mock'),

    'access_token_ttl' => (int) env('ACCESS_TOKEN_TTL_SECONDS', 3600),
    'refresh_token_ttl' => (int) env('REFRESH_TOKEN_TTL_SECONDS', 2592000),
];

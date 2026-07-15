<?php

return [
    'driver' => env('SMS_DRIVER', 'hubtel'),

    'hubtel' => [
        'client_id' => filled(env('HUBTEL_CLIENT_ID')) ? trim((string) env('HUBTEL_CLIENT_ID')) : null,
        'client_secret' => filled(env('HUBTEL_CLIENT_SECRET')) ? trim((string) env('HUBTEL_CLIENT_SECRET')) : null,
        'sender_id' => trim((string) env('HUBTEL_SENDER_ID', 'HBDRP')),
        'endpoint' => trim((string) env('HUBTEL_SMS_ENDPOINT', 'https://smsc.hubtel.com/v1/messages/send')),
        'callback_url' => filled(env('HUBTEL_CALLBACK_URL')) ? trim((string) env('HUBTEL_CALLBACK_URL')) : null,
        'timeout' => (int) env('HUBTEL_TIMEOUT_SECONDS', 15),
    ],
];

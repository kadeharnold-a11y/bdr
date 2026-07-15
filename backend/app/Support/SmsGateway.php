<?php

namespace App\Support;

use App\Exceptions\OtpDeliveryException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsGateway
{
    /** @throws OtpDeliveryException */
    public static function send(string $phone, string $message): void
    {
        if (! DeliveryConfig::smsReady()) {
            throw new OtpDeliveryException(
                'SMS gateway is not configured. Set HUBTEL_CLIENT_ID and HUBTEL_CLIENT_SECRET in your .env file, then run: php artisan config:clear',
                'SMS_NOT_CONFIGURED',
            );
        }

        self::sendViaHubtel($phone, $message);
    }

    /** @throws OtpDeliveryException */
    private static function sendViaHubtel(string $phone, string $message): void
    {
        $clientId = config('sms.hubtel.client_id');
        $clientSecret = config('sms.hubtel.client_secret');
        $senderId = config('sms.hubtel.sender_id');
        $endpoint = config('sms.hubtel.endpoint');
        $to = '+233'.ltrim($phone, '0');

        $payload = [
            'From' => $senderId,
            'To' => $to,
            'Content' => $message,
        ];

        if ($callback = config('sms.hubtel.callback_url')) {
            $payload['RegisteredDelivery'] = true;
            $payload['ClientReference'] = 'hbdrp-'.now()->timestamp;
        }

        Log::info('[SMS][hubtel] sending', [
            'to' => $to,
            'from' => $senderId,
            'endpoint' => $endpoint,
        ]);

        $response = Http::withBasicAuth($clientId, $clientSecret)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('sms.hubtel.timeout', 15))
            ->post($endpoint, $payload);

        if (self::hubtelSucceeded($response)) {
            Log::info('[SMS][hubtel] delivered', [
                'to' => $to,
                'messageId' => data_get($response->json(), 'MessageId'),
                'status' => data_get($response->json(), 'Status'),
            ]);

            return;
        }

        $detail = self::hubtelErrorDetail($response);

        Log::error('[SMS][hubtel] delivery failed', [
            'to' => $to,
            'httpStatus' => $response->status(),
            'body' => $response->body(),
            'detail' => $detail,
        ]);

        throw new OtpDeliveryException(
            'Could not send SMS. '.$detail,
            'SMS_DELIVERY_FAILED',
        );
    }

    private static function hubtelSucceeded(Response $response): bool
    {
        if (in_array($response->status(), [200, 201], true)) {
            $body = $response->json();
            if (! is_array($body)) {
                return true;
            }
            if (! array_key_exists('Status', $body)) {
                return true;
            }

            return (int) $body['Status'] === 0;
        }

        return false;
    }

    private static function hubtelErrorDetail(Response $response): string
    {
        $body = $response->json();
        if (is_array($body)) {
            $description = $body['StatusDescription'] ?? $body['Message'] ?? $body['Detail'] ?? null;
            if (is_string($description) && $description !== '') {
                return $description;
            }
        }

        if ($response->status() === 401) {
            return 'Hubtel rejected the API credentials (HTTP 401). Verify HUBTEL_CLIENT_ID and HUBTEL_CLIENT_SECRET.';
        }

        if ($response->status() === 402) {
            return 'Hubtel SMS wallet has insufficient funds. Top up your Programmable SMS account in the Hubtel dashboard.';
        }

        return 'Hubtel returned HTTP '.$response->status().'. Check laravel.log for the full response.';
    }
}

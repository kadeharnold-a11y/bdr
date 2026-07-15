<?php

namespace App\Exceptions;

use RuntimeException;

class OtpDeliveryException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'OTP_DELIVERY_FAILED',
    ) {
        parent::__construct($message);
    }
}

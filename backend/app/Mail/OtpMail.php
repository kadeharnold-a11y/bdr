<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $purpose, // 'register' | 'login'
        public int $ttlSeconds = 600,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your HBDRP verification code',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.otp',
            with: [
                'code' => $this->code,
                'purpose' => $this->purpose,
                'expiresMinutes' => (int) ceil($this->ttlSeconds / 60),
            ],
        );
    }
}

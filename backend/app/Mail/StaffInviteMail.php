<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $staffId,
        public string $fullName,
        public string $role,
        public string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your HBDRP back-office account',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.staff-invite',
            with: [
                'staffId' => $this->staffId,
                'fullName' => $this->fullName,
                'role' => $this->role,
                'temporaryPassword' => $this->temporaryPassword,
            ],
        );
    }
}

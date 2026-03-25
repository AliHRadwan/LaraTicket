<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $verificationUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your Email Address',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.verify-email',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

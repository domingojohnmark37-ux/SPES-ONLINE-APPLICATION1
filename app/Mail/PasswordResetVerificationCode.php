<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $pin) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your SPES password reset confirmation code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-verification-code',
            with: ['pin' => $this->pin],
        );
    }
}

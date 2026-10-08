<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicantEmailChangeCode extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $pin)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your SPES email change verification code');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.applicant-email-change-code',
            with: ['pin' => $this->pin],
        );
    }
}

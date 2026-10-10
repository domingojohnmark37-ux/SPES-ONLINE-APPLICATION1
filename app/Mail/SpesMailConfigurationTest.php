<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SpesMailConfigurationTest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $portalUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Special Program for Employment of Students (SPES): email configuration test',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.spes-mail-test',
            with: ['portalUrl' => $this->portalUrl],
        );
    }
}

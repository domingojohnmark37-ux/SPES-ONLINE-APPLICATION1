<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PersonalSecurityRecoveryCode extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your SPES security recovery code');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.personal-security-recovery-code');
    }
}

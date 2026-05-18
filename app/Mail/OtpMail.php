<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OtpMail extends Mailable
{
    public function __construct(public readonly string $otp)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Corleone App Hub OTP',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            text: 'emails.otp-text',
            with: [
                'otp' => $this->otp,
            ],
        );
    }
}

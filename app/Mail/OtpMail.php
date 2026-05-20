<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OtpMail extends Mailable
{
    public function __construct(
        public readonly string $otp,
        public readonly string $otpSubject = 'Your Corleone App Hub OTP',
        public readonly string $name = 'Corleone App Hub User',
        public readonly int $expiresInMinutes = 10,
    )
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->otpSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
            text: 'emails.otp-text',
            with: [
                'otp' => $this->otp,
                'name' => $this->name,
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }
}

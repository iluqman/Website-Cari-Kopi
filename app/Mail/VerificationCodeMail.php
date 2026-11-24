<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Email berisi kode verifikasi 6 digit untuk pendaftaran. */
class VerificationCodeMail extends Mailable
{
    public function __construct(
        public string $code,
        public int $minutes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kode verifikasi CariKopi');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.verification-code');
    }
}

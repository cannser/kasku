<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Email berisi tautan reset password. Teks polos saja (tanpa HTML), sekali pakai. */
class ResetPasswordLink extends Mailable
{
    public function __construct(
        public string $username,
        public string $url,
        public int $minutes,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset password KasKu');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.reset-password');
    }
}

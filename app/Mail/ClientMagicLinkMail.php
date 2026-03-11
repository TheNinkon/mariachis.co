<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientMagicLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $magicUrl,
        public int $expiresInMinutes
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu enlace de acceso a Mariachis.co',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'front.auth.emails.client-magic-link',
            with: [
                'user' => $this->user,
                'magicUrl' => $this->magicUrl,
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }
}

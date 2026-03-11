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
        $isFirstAccess = trim((string) ($this->user->first_name ?? '')) === ''
            || trim((string) ($this->user->last_name ?? '')) === '';

        return new Envelope(
            subject: $isFirstAccess
                ? 'Bienvenido a Mariachis.co: confirma tu acceso seguro'
                : 'Tu enlace seguro para entrar a Mariachis.co',
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

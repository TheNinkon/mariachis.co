<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemSmtpTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipient,
        public string $mailer,
        public string $fromAddress,
        public string $fromName
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Prueba SMTP de Mariachis.co',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.system-smtp-test',
            with: [
                'recipient' => $this->recipient,
                'mailer' => $this->mailer,
                'fromAddress' => $this->fromAddress,
                'fromName' => $this->fromName,
                'sentAt' => now(),
            ],
        );
    }
}

<?php

namespace App\Mail;

use App\Services\EmailTemplateService;
use App\Support\EmailTemplates\EmailTemplateCatalog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SystemSmtpTestMail extends Mailable
{
    use Queueable, SerializesModels;

    private ?array $renderedTemplate = null;

    public function __construct(
        public string $recipient,
        public string $mailerName,
        public string $fromAddress,
        public string $fromName
    ) {
    }

    public function envelope(): Envelope
    {
        $rendered = $this->resolvedTemplate();

        return new Envelope(
            subject: $rendered['subject'] ?? 'Prueba SMTP de Mariachis.co',
        );
    }

    public function content(): Content
    {
        $rendered = $this->resolvedTemplate();

        if ($rendered !== null) {
            return new Content(
                htmlString: $rendered['html'],
            );
        }

        return new Content(
            view: 'emails.system-smtp-test',
            with: [
                'recipient' => $this->recipient,
                'mailer' => $this->mailerName,
                'fromAddress' => $this->fromAddress,
                'fromName' => $this->fromName,
                'sentAt' => now(),
            ],
        );
    }

    /**
     * @return array{subject:string,html:string}|null
     */
    private function resolvedTemplate(): ?array
    {
        if ($this->renderedTemplate !== null) {
            return $this->renderedTemplate;
        }

        $this->renderedTemplate = app(EmailTemplateService::class)->renderActive(
            EmailTemplateCatalog::KEY_SYSTEM_SMTP_TEST,
            [
                'recipient' => $this->recipient,
                'mailer' => $this->mailerName,
                'fromName' => $this->fromName,
                'fromAddress' => $this->fromAddress,
                'sentAt' => now()->format('Y-m-d H:i:s'),
            ]
        );

        return $this->renderedTemplate;
    }
}

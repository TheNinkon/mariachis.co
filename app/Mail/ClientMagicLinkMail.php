<?php

namespace App\Mail;

use App\Models\User;
use App\Services\EmailTemplateService;
use App\Support\EmailTemplates\EmailTemplateCatalog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientMagicLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    private ?array $renderedTemplate = null;

    public function __construct(
        public User $user,
        public string $magicUrl,
        public int $expiresInMinutes
    ) {
    }

    public function envelope(): Envelope
    {
        $rendered = $this->resolvedTemplate();

        return new Envelope(
            subject: $rendered['subject'] ?? $this->fallbackSubject(),
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
            view: 'front.auth.emails.client-magic-link',
            with: [
                'user' => $this->user,
                'magicUrl' => $this->magicUrl,
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }

    private function fallbackSubject(): string
    {
        return $this->isFirstAccess()
            ? 'Bienvenido a Mariachis.co: confirma tu acceso seguro'
            : 'Tu enlace seguro para entrar a Mariachis.co';
    }

    private function isFirstAccess(): bool
    {
        return trim((string) ($this->user->first_name ?? '')) === ''
            || trim((string) ($this->user->last_name ?? '')) === '';
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
            EmailTemplateCatalog::KEY_CLIENT_MAGIC_LINK,
            [
                'logoUrl' => 'https://mariachis.co/front/assets/logo-wordmark.png',
                'emailTitle' => $this->isFirstAccess() ? 'Bienvenido a Mariachis.co' : 'Tu acceso seguro a Mariachis.co',
                'emailLead' => $this->isFirstAccess()
                    ? 'Haz clic en el botón para entrar y terminar de preparar tu cuenta en Mariachis.co.'
                    : 'Usa este enlace de un solo uso para entrar a tu cuenta de cliente en Mariachis.co.',
                'user_email' => $this->user->email,
                'magicUrl' => $this->magicUrl,
                'buttonLabel' => $this->isFirstAccess() ? 'Continuar en Mariachis.co' : 'Entrar a mi cuenta',
                'expiresInMinutes' => $this->expiresInMinutes,
                'closingLine' => trim((string) ($this->user->first_name ?: $this->user->display_name)) !== ''
                    ? 'Gracias, '.trim((string) ($this->user->first_name ?: $this->user->display_name)).'.'
                    : 'Gracias por confiar en Mariachis.co.',
            ]
        );

        return $this->renderedTemplate;
    }
}

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

class MariachiWelcomeVerifyMail extends Mailable
{
    use Queueable, SerializesModels;

    private ?array $renderedTemplate = null;

    public function __construct(
        public User $user,
        public string $verifyUrl,
        public int $expiresInDays
    ) {
    }

    public function envelope(): Envelope
    {
        $rendered = $this->resolvedTemplate();

        return new Envelope(
            subject: $rendered['subject'] ?? 'Bienvenido a Mariachis.co: verifica tu correo para empezar',
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
            view: 'front.auth.emails.mariachi-welcome-verify',
            with: [
                'user' => $this->user,
                'verifyUrl' => $this->verifyUrl,
                'expiresInDays' => $this->expiresInDays,
                'loginUrl' => route('mariachi.login'),
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

        $displayName = trim((string) ($this->user->first_name ?: $this->user->display_name));

        $this->renderedTemplate = app(EmailTemplateService::class)->renderActive(
            EmailTemplateCatalog::KEY_MARIACHI_WELCOME_VERIFY,
            [
                'logoUrl' => 'https://mariachis.co/front/assets/logo-wordmark.png',
                'emailTitle' => 'Bienvenido a Mariachis.co',
                'emailLead' => 'Tu cuenta de mariachi ya está creada. Verifica tu correo para proteger tu acceso y continuar con la configuración de tu perfil.',
                'user_email' => $this->user->email,
                'verifyUrl' => $this->verifyUrl,
                'buttonLabel' => 'Verificar mi correo',
                'loginUrl' => route('mariachi.login'),
                'loginLabel' => 'Entrar al panel mariachi',
                'expiresInDays' => $this->expiresInDays,
                'securityLine' => 'Este enlace caduca en '.$this->expiresInDays.' días. Si no solicitaste esta cuenta, puedes ignorar este correo y no se aplicará ningún cambio.',
                'closingLine' => $displayName !== ''
                    ? 'Gracias, '.$displayName.'.'
                    : 'Gracias por unirte a Mariachis.co.',
            ]
        );

        return $this->renderedTemplate;
    }
}

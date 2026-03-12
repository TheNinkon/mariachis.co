<?php

namespace App\Notifications;

use App\Services\EmailTemplateService;
use App\Support\EmailTemplates\EmailTemplateCatalog;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class ClientPasswordSetupNotification extends ResetPassword
{
    use Queueable;

    public function toMail($notifiable)
    {
        $isAccountCreationFlow = trim((string) ($notifiable->first_name ?? '')) === ''
            || trim((string) ($notifiable->last_name ?? '')) === ''
            || $notifiable->email_verified_at === null;

        $variables = [
            'logoUrl' => 'https://mariachis.co/front/assets/logo-wordmark.png',
            'emailSubject' => $isAccountCreationFlow
                ? 'Crea tu contraseña en Mariachis.co'
                : 'Restablece tu contraseña en Mariachis.co',
            'emailTitle' => $isAccountCreationFlow
                ? 'Crea tu contraseña'
                : 'Define tu nueva contraseña',
            'emailLead' => $isAccountCreationFlow
                ? 'Has solicitado crear tu acceso en Mariachis.co. Elige una contraseña para continuar y terminar de preparar tu cuenta.'
                : 'Has solicitado restablecer la contraseña de tu cuenta de Mariachis.co. Elige una nueva para continuar.',
            'setupUrl' => $this->resetUrl($notifiable),
            'expiresInMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
            'email' => $notifiable->getEmailForPasswordReset(),
            'buttonLabel' => $isAccountCreationFlow
                ? 'Crear mi contraseña'
                : 'Definir contraseña',
            'securityLine' => $isAccountCreationFlow
                ? 'Este enlace caduca por seguridad. Si no solicitaste crear tu acceso, puedes ignorar este correo y no haremos ningún cambio.'
                : 'Este enlace caduca por seguridad. Si no solicitaste restablecer tu contraseña, puedes ignorar este correo y no se aplicará ningún cambio.',
            'homeUrl' => url('/'),
            'homeLabel' => 'Ir a Mariachis.co',
            'closingLine' => $isAccountCreationFlow
                ? 'Nos vemos pronto en Mariachis.co.'
                : 'Gracias por seguir con nosotros.',
        ];

        $rendered = app(EmailTemplateService::class)->renderActive(
            EmailTemplateCatalog::KEY_CLIENT_PASSWORD_SETUP,
            $variables
        );

        if ($rendered !== null) {
            return (new MailMessage)
                ->subject($rendered['subject'])
                ->view('emails.raw-html', ['html' => $rendered['html']]);
        }

        return (new MailMessage)
            ->subject($variables['emailSubject'])
            ->view('front.auth.emails.client-password-setup', $variables);
    }

    protected function resetUrl($notifiable): string
    {
        return route('client.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}

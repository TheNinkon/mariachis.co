<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class ClientPasswordSetupNotification extends ResetPassword
{
    use Queueable;

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Define tu contraseña en Mariachis.co')
            ->view('front.auth.emails.client-password-setup', [
                'setupUrl' => $this->resetUrl($notifiable),
                'expiresInMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60),
                'email' => $notifiable->getEmailForPasswordReset(),
                'displayName' => trim((string) ($notifiable->display_name ?? $notifiable->name ?? '')),
            ]);
    }

    protected function resetUrl($notifiable): string
    {
        return route('client.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}

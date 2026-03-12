<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;

class MariachiPasswordResetNotification extends ResetPassword
{
    use Queueable;

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Restablece tu contraseña de partner en Mariachis.co')
            ->line('Haz clic en el botón para definir una nueva contraseña y seguir gestionando tu portal de partner.')
            ->action('Restablecer contraseña', $this->resetUrl($notifiable))
            ->line('Si no solicitaste este cambio, puedes ignorar este correo sin hacer nada.');
    }

    protected function resetUrl($notifiable): string
    {
        return route('mariachi.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}

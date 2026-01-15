<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPasswordNotification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPasswordNotification
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $logoUrl = url(asset('images/logo.svg'));

        return (new MailMessage)
            ->subject('Restablecer Contraseña - Coopuertos')
            ->view('emails.reset-password', [
                'url' => $url,
                'logoUrl' => $logoUrl,
            ]);
    }
}

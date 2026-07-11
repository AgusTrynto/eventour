<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    public function __construct(
        private readonly string $token
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $broker = config('auth.defaults.passwords');
        $expire = config("auth.passwords.{$broker}.expire", 60);
        $resetUrl = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Reset Password EvenTour')
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Kami menerima permintaan untuk mereset password akun EvenTour kamu.')
            ->action('Reset Password', $resetUrl)
            ->line("Link ini berlaku selama {$expire} menit.")
            ->line('Kalau kamu tidak meminta reset password, abaikan email ini.');
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordCode extends Notification
{
    use Queueable;

    public function __construct(public string $code)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode Reset Password — ' . config('app.name'))
            ->greeting('Halo, ' . $notifiable->name)
            ->line('Kami menerima permintaan reset password untuk akun Anda.')
            ->line('Kode verifikasi Anda:')
            ->line('**' . $this->code . '**')
            ->line('Kode ini berlaku selama 10 menit.')
            ->line('Jika Anda tidak meminta reset password, abaikan email ini.')
            ->salutation('Terima kasih, ' . config('app.name'));
    }
}

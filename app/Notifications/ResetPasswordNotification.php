<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * SSMS-UC-100 A2: e-mail with the password reset link (in Malay).
 * $role is 'vendor' or 'admin', so the link opens the right reset page.
 */
class ResetPasswordNotification extends Notification
{
    public function __construct(
        public string $token,
        public string $role,
    ) {
    }

    /** Send by e-mail */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** Link to the reset page for this user */
    public static function resetUrl(string $role, string $token, string $email): string
    {
        return route('password.reset', ['role' => $role, 'token' => $token, 'email' => $email]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $broker = $this->role === 'admin' ? 'administrators' : 'vendors';
        $minutes = config("auth.passwords.$broker.expire");
        $name = $notifiable->name ?? $notifiable->pic_name;

        return (new MailMessage)
            ->subject('Set Semula Kata Laluan - ' . config('speed.full_name'))
            ->greeting('Salam sejahtera, ' . $name)
            ->line('Kami menerima permintaan untuk menetapkan semula kata laluan akaun anda.')
            ->action('Set Semula Kata Laluan', self::resetUrl($this->role, $this->token, $notifiable->email))
            ->line("Pautan ini sah selama $minutes minit.")
            ->line('Jika anda tidak membuat permintaan ini, abaikan e-mel ini. Kata laluan anda tidak akan berubah.')
            ->salutation('Terima kasih, ' . config('speed.council'));
    }
}
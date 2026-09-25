<?php

namespace App\Notifications;

use App\Models\Tenant\Setting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffResetPasswordNotification extends Notification
{
    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('tenant.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $shopName = Setting::get('shop_name', config('app.name'));

        return (new MailMessage)
            ->subject(__('messages.password_reset_subject', ['shop' => $shopName]))
            ->view('emails.staff-reset-password', [
                'url' => $url,
                'user' => $notifiable,
                'shopName' => $shopName,
                'expireMinutes' => config('auth.passwords.tenant_users.expire', 60),
            ]);
    }
}

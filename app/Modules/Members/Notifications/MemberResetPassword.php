<?php

namespace App\Modules\Members\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberResetPassword extends Notification
{
    public function __construct(#[\SensitiveParameter] public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('members.password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)
            ->subject(__('larawebkit-members.mail.reset_subject', ['site' => setting('general.site_name')]))
            ->greeting(__('larawebkit-members.mail.hello', ['name' => $notifiable->name]))
            ->line(__('larawebkit-members.mail.reset_line'))
            ->action(__('larawebkit-members.mail.reset_button'), $url)
            ->line(__('larawebkit-members.mail.reset_expire', ['minutes' => config('auth.passwords.members.expire', 60)]))
            ->line(__('larawebkit-members.mail.reset_ignore'));
    }
}

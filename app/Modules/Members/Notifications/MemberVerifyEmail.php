<?php

namespace App\Modules\Members\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class MemberVerifyEmail extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function verificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute('members.verification.verify', now()->addHours(48), [
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('larawebkit-members.mail.verify_subject', ['site' => setting('general.site_name')]))
            ->greeting(__('larawebkit-members.mail.hello', ['name' => $notifiable->name]))
            ->line(__('larawebkit-members.mail.verify_line'))
            ->action(__('larawebkit-members.mail.verify_button'), $this->verificationUrl($notifiable))
            ->line(__('larawebkit-members.mail.verify_ignore'));
    }
}

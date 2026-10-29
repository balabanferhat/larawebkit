<?php

namespace App\Modules\Settings\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** "Send test e-mail" on the settings page: sent right away (not queued) so errors show at once. */
class TestMail extends Mailable
{
    public function envelope(): Envelope
    {
        return new Envelope(subject: __('larawebkit-settings.mail_test.subject', ['site' => setting('general.site_name')]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.test', with: ['mailer' => (string) config('mail.default'), 'sentAt' => now()->toDayDateTimeString()]);
    }
}

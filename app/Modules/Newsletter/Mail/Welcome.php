<?php

namespace App\Modules\Newsletter\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent after confirming the subscription when a lead magnet is set (Settings → Newsletter). */
class Welcome extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $download) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('larawebkit-newsletter.mail.welcome_subject', ['site' => setting('general.site_name')]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.newsletter-welcome', with: ['download' => $this->download]);
    }
}

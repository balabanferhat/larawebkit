<?php

namespace App\Modules\Newsletter\Mail;

use App\Modules\Newsletter\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ConfirmSubscription extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Subscriber $subscriber, public string $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('larawebkit-mail.newsletter.subject', ['site' => setting('general.site_name')], $this->subscriber->locale));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.newsletter-confirm', with: [
            'url' => route('newsletter.confirm', $this->token),
            'locale' => $this->subscriber->locale,
        ]);
    }
}

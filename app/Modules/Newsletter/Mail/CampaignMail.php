<?php

namespace App\Modules\Newsletter\Mail;

use App\Modules\Content\Support\HtmlSanitizer;
use App\Modules\Newsletter\Models\Campaign;
use App\Modules\Newsletter\Models\Subscriber;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;

/**
 * A campaign e-mail to one subscriber, with List-Unsubscribe and List-Unsubscribe-Post
 * (one-click, RFC 8058) headers and a visible unsubscribe link. No tracking pixels.
 */
class CampaignMail extends Mailable
{
    public function __construct(public Campaign $campaign, public Subscriber $subscriber, public bool $test = false)
    {
        $this->locale($campaign->locale ?: ($subscriber->locale ?: app()->getLocale()));
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: ($this->test ? '[TEST] ' : '').$this->campaign->subject);
    }

    public function headers(): Headers
    {
        $url = $this->unsubscribeUrl();

        return new Headers(text: [
            'List-Unsubscribe' => '<'.$url.'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            'X-Campaign-Id' => (string) $this->campaign->id,
        ]);
    }

    public function content(): Content
    {
        return new Content(view: 'mail.newsletter-campaign', with: [
            'campaign' => $this->campaign,
            'body' => app(HtmlSanitizer::class)->sanitize($this->campaign->body),
            'unsubscribe' => $this->unsubscribeUrl(),
        ]);
    }

    private function unsubscribeUrl(): string
    {
        return $this->subscriber->id ? $this->subscriber->unsubscribeUrl() : url('/');
    }
}

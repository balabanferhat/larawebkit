<?php

namespace App\Modules\Events\Mail;

use App\Modules\Events\Ics;
use App\Modules\Events\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent on registration (with the calendar file) and, with $link = true, by the admin
 * "send link to registrants" action. Both carry the signed link to the registrant's page.
 */
class RegistrationConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Registration $registration, public bool $link = false)
    {
        $this->locale($registration->event->locale);
    }

    public function envelope(): Envelope
    {
        $key = $this->link ? 'larawebkit-events.mail.link_subject' : 'larawebkit-events.mail.confirmation_subject';

        return new Envelope(subject: __($key, ['event' => $this->registration->event->title]));
    }

    public function content(): Content
    {
        $event = $this->registration->event;

        return new Content(markdown: 'mail.event-registration', with: [
            'event' => $event,
            'registration' => $this->registration,
            'link' => $this->link,
            'online' => $event->isOnline() ? (string) $event->online_url : '',
            'manage' => $this->registration->url(),
        ]);
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        $event = $this->registration->event;

        return [Attachment::fromData(fn () => Ics::make($event, $this->registration), $event->slug.'.ics')->withMime('text/calendar')];
    }
}

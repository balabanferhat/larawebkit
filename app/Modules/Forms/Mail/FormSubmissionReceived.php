<?php

namespace App\Modules\Forms\Mail;

use App\Modules\Forms\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the form's recipients; "Reply" goes to the person who filled it in. */
class FormSubmissionReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public FormSubmission $submission) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('larawebkit-forms.mail.received_subject', ['form' => $this->submission->form->name, 'site' => setting('general.site_name')]),
            replyTo: $this->submission->email ? [new Address($this->submission->email)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.form-submission', with: ['submission' => $this->submission, 'rows' => $this->submission->readable()]);
    }
}

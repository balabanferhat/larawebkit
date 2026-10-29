<?php

namespace App\Modules\Forms\Mail;

use App\Modules\Core\Downloads\SignedDownload;
use App\Modules\Forms\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Optional confirmation to the sender. A form with a download (lead magnet) always sends it: the
 * signed, expiring link arrives in the inbox, which also confirms the address.
 */
class FormConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public FormSubmission $submission) {}

    public function envelope(): Envelope
    {
        $form = $this->submission->form;

        return new Envelope(subject: filled($form->confirmation_subject) ? $form->confirmation_subject : __('larawebkit-forms.mail.confirmation_subject', ['site' => setting('general.site_name')]));
    }

    public function content(): Content
    {
        $form = $this->submission->form;

        return new Content(markdown: 'mail.form-confirmation', with: [
            'body' => filled($form->confirmation_body) ? $form->confirmation_body : __('larawebkit-forms.mail.confirmation_body'),
            'download' => $form->hasDownload() ? SignedDownload::url($form->download_path, $form->download_name ?: basename($form->download_path)) : null,
            'rows' => $this->submission->readable(),
        ]);
    }
}

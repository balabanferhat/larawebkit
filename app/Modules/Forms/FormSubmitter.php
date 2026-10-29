<?php

namespace App\Modules\Forms;

use App\Modules\Forms\Mail\FormConfirmation;
use App\Modules\Forms\Mail\FormSubmissionReceived;
use App\Modules\Forms\Models\Form;
use App\Modules\Forms\Models\FormSubmission;
use App\Modules\Logs\Http\Middleware\LogAccess;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/** Validates a public submission against the form's fields, stores it with its files and sends the e-mails. */
class FormSubmitter
{
    public function submit(Form $form, Request $request): FormSubmission
    {
        $validated = Validator::make($request->all() + ['fields' => []], FieldTypes::rules($form), [], FieldTypes::attributes($form))
            ->validate()['fields'] ?? [];

        $data = [];
        $files = [];
        $consent = null;
        foreach ($form->fieldList() as $field) {
            $key = $field['key'];
            $value = $validated[$key] ?? null;
            if ($field['type'] === 'file') {
                if ($value instanceof UploadedFile) {
                    $files[] = $this->storeFile($form, $key, $value);
                }

                continue;
            }
            if ($field['type'] === 'consent') {
                $consent = $field;
                $data[$key] = true;

                continue;
            }
            $data[$key] = match ($field['type']) {
                'checkbox' => (bool) $value,
                'email' => $value !== null ? Str::lower(trim((string) $value)) : null,
                default => $value !== null ? trim((string) $value) : null,
            };
        }

        $submission = $form->submissions()->create([
            'data' => $data,
            'files' => $files ?: null,
            'email' => ($emailKey = $form->emailField()) ? ($data[$emailKey] ?? null) : null,
            'consent_version' => $consent ? (int) ($consent['consent_version'] ?? 1) : null,
            'consent_text' => $consent ? (string) ($consent['label'] ?? '') : null,
            'locale' => app()->getLocale(),
            'ip_hash' => class_exists(LogAccess::class) ? LogAccess::hashIp((string) $request->ip()) : null,
            'source_url' => mb_substr((string) $request->headers->get('referer'), 0, 500) ?: null,
        ]);

        if ($recipients = $form->recipients()) {
            Mail::to($recipients)->queue(new FormSubmissionReceived($submission));
        }
        if ($submission->email && ($form->send_confirmation || $form->hasDownload())) {
            Mail::to($submission->email)->locale(app()->getLocale())->queue(new FormConfirmation($submission));
        }

        return $submission;
    }

    /** @return array{field: string, path: string, name: string, size: int, mime: string} */
    private function storeFile(Form $form, string $field, UploadedFile $file): array
    {
        // The extension comes from the content (what the mimes rule checked), never from the visitor's file
        // name: "invoice.php" with PDF content is stored as .pdf.
        $extension = strtolower((string) $file->extension());
        $extension = in_array($extension, FieldTypes::FILE_TYPES, true) ? $extension : 'bin';
        $path = $file->storeAs(
            trim((string) config('larawebkit-forms.directory', 'form-uploads'), '/').'/'.$form->id.'/'.Str::uuid(),
            Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'file').'.'.$extension,
            ['disk' => (string) config('larawebkit-forms.disk', 'local')],
        );

        return ['field' => $field, 'path' => (string) $path, 'name' => mb_substr($file->getClientOriginalName(), 0, 190), 'size' => (int) $file->getSize(), 'mime' => (string) $file->getMimeType()];
    }
}

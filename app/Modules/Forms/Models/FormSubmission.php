<?php

namespace App\Modules\Forms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FormSubmission extends Model
{
    public const STATUSES = ['new', 'read', 'replied', 'archived', 'spam'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['data' => 'array', 'files' => 'array', 'consent_version' => 'integer'];
    }

    protected static function booted(): void
    {
        // Uploaded files go with the submission (GDPR/KVKK: nothing left behind).
        static::deleting(function (FormSubmission $submission) {
            foreach ((array) $submission->files as $file) {
                Storage::disk((string) config('larawebkit-forms.disk', 'local'))->delete($file['path'] ?? '');
            }
        });
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /** Field label => readable value, in the form's field order. */
    public function readable(): array
    {
        $out = [];
        $fields = $this->form?->fieldList() ?? [];
        $data = (array) $this->data;
        foreach ($fields as $field) {
            if ($field['type'] === 'file') {
                $out[$field['label'] ?? $field['key']] = collect((array) $this->files)->where('field', $field['key'])->pluck('name')->implode(', ');

                continue;
            }
            $value = $data[$field['key']] ?? null;
            $out[$field['label'] ?? $field['key']] = is_bool($value) ? ($value ? __('larawebkit-forms.yes') : __('larawebkit-forms.no')) : (is_array($value) ? implode(', ', $value) : (string) $value);
        }

        return $out;
    }
}

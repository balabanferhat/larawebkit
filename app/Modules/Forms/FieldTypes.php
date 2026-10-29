<?php

namespace App\Modules\Forms;

use App\Modules\Forms\Models\Form;
use Illuminate\Validation\Rule;

/**
 * Field types for admin-defined forms and the validation rules they produce.
 * A field: key, type, label, required, placeholder, help, width (half|full),
 * options (select/radio), max (length), accept + max_kb (file), consent_version.
 */
final class FieldTypes
{
    public const TYPES = ['text', 'email', 'phone', 'textarea', 'select', 'radio', 'checkbox', 'date', 'file', 'consent'];

    /** Extensions a file field may allow; anything executable is never accepted. */
    public const FILE_TYPES = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods', 'zip'];

    /** @return array<string, list<mixed>> */
    public static function rules(Form $form): array
    {
        $rules = [];
        foreach ($form->fieldList() as $field) {
            $key = 'fields.'.$field['key'];
            $required = ! empty($field['required']) || $field['type'] === 'consent' ? 'required' : 'nullable';
            $rules[$key] = match ($field['type']) {
                'email' => [$required, 'email:rfc', 'max:190'],
                'phone' => [$required, 'string', 'max:40', 'regex:/^[0-9+().\s\-\/]{5,40}$/'],
                'textarea' => [$required, 'string', 'max:'.min(10000, (int) ($field['max'] ?? 5000))],
                'select', 'radio' => [$required, 'string', Rule::in(self::options($field))],
                'checkbox' => [! empty($field['required']) ? 'accepted' : 'nullable', 'boolean'],
                'consent' => ['accepted'],
                'date' => [$required, 'date'],
                'file' => [$required, 'file', 'max:'.min((int) config('larawebkit-forms.max_file_kb', 10240), (int) ($field['max_kb'] ?? 5120)),
                    'mimes:'.implode(',', array_intersect(self::accept($field), self::FILE_TYPES) ?: ['pdf'])],
                default => [$required, 'string', 'max:'.min(1000, (int) ($field['max'] ?? 255))],
            };
        }

        return $rules;
    }

    /** @return array<string, string> attribute names for validation messages */
    public static function attributes(Form $form): array
    {
        return collect($form->fieldList())->mapWithKeys(fn ($f) => ['fields.'.$f['key'] => $f['label'] ?? $f['key']])->all();
    }

    /** @return list<string> */
    public static function options(array $field): array
    {
        $options = $field['options'] ?? [];
        if (is_string($options)) {
            $options = preg_split('/\R/', $options);
        }

        return array_values(array_filter(array_map('trim', (array) $options), fn ($o) => $o !== ''));
    }

    /** @return list<string> */
    public static function accept(array $field): array
    {
        $accept = $field['accept'] ?? 'pdf';

        return array_values(array_filter(array_map(fn ($e) => strtolower(trim($e, " .\t")), is_array($accept) ? $accept : explode(',', (string) $accept))));
    }
}

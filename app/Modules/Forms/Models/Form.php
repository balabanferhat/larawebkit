<?php

namespace App\Modules\Forms\Models;

use App\Modules\Logs\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An admin-defined form. `fields` is a list of field definitions (see FieldTypes);
 * submissions are stored in form_submissions and e-mailed to `notify_emails`.
 */
class Form extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fields' => 'array', 'send_confirmation' => 'boolean', 'is_active' => 'boolean'];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class)->latest('id');
    }

    /** @return list<array<string, mixed>> */
    public function fieldList(): array
    {
        return array_values(array_filter((array) $this->fields, fn ($f) => is_array($f) && filled($f['key'] ?? null) && filled($f['type'] ?? null)));
    }

    /** The first e-mail field: used as reply-to and for the confirmation e-mail. */
    public function emailField(): ?string
    {
        foreach ($this->fieldList() as $field) {
            if ($field['type'] === 'email') {
                return $field['key'];
            }
        }

        return null;
    }

    /** @return list<string> */
    public function recipients(): array
    {
        $list = array_filter(array_map('trim', preg_split('/[,;\s]+/', (string) $this->notify_emails)), fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL));

        return $list !== [] ? array_values($list) : array_filter([(string) setting('general.contact_email')]);
    }

    public function hasDownload(): bool
    {
        return filled($this->download_path);
    }

    public function auditLabel(): string
    {
        return $this->name;
    }
}

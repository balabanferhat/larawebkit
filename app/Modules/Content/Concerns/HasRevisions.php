<?php

namespace App\Modules\Content\Concerns;

use App\Modules\Content\Models\Revision;
use App\Modules\Logs\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Every save stores a version of the editable fields; autosaves are kept as
 * a single rolling draft per record. Revisions can be diffed and restored.
 */
trait HasRevisions
{
    public static function bootHasRevisions(): void
    {
        static::saved(function (Model $model) {
            if ($model->wasRecentlyCreated || $model->wasChanged($model->revisionFields())) {
                $model->createRevision();
            }
        });
    }

    /** @return list<string> */
    public function revisionFields(): array
    {
        return ['title', 'slug', 'content', 'excerpt', 'summary', 'faq', 'sources', 'meta_title', 'meta_description', 'keywords', 'featured_media_id'];
    }

    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisionable')->latest('id');
    }

    /** @return array<string, mixed> */
    public function revisionSnapshot(): array
    {
        $out = [];
        foreach ($this->revisionFields() as $field) {
            if (array_key_exists($field, $this->getAttributes())) {
                $out[$field] = $this->getAttribute($field);
            }
        }

        return $out;
    }

    public function createRevision(?array $data = null, bool $autosave = false): Revision
    {
        if ($autosave) {
            $this->revisions()->where('is_autosave', true)->delete();
        }

        return $this->revisions()->create([
            'user_id' => auth()->id(),
            'data' => $data ?? $this->revisionSnapshot(),
            'is_autosave' => $autosave,
            'created_at' => now(),
        ]);
    }

    public function latestAutosave(): ?Revision
    {
        return $this->revisions()->where('is_autosave', true)->first();
    }

    public function restoreRevision(Revision $revision): void
    {
        $data = array_intersect_key($revision->data, array_flip($this->revisionFields()));
        $this->fill($data);
        AuditLogger::withReason(__('larawebkit-content.revisions.restored_reason', ['id' => $revision->id]), fn () => $this->save());
    }
}

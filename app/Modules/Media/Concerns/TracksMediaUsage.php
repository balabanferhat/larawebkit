<?php

namespace App\Modules\Media\Concerns;

use App\Modules\Media\Models\Media;
use App\Modules\Media\Models\MediaUsage;
use App\Modules\Media\Support\ContentImages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Keeps media_usages in sync with featured_media_id and inline editor images,
 * so the media library can show "used in N posts/pages" and warn before deleting.
 */
trait TracksMediaUsage
{
    public static function bootTracksMediaUsage(): void
    {
        static::saved(fn (Model $model) => $model->syncMediaUsage());
        static::deleted(function (Model $model) {
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                $model->mediaUsages()->delete();
            }
        });
    }

    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function mediaUsages(): MorphMany
    {
        return $this->morphMany(MediaUsage::class, 'usable');
    }

    public function syncMediaUsage(): void
    {
        $wanted = [];
        if ($this->featured_media_id) {
            $wanted[] = [(int) $this->featured_media_id, 'featured'];
        }
        foreach (ContentImages::ids($this->content ?? '') as $id) {
            $wanted[] = [$id, 'content'];
        }

        $existing = Media::whereIn('id', array_column($wanted, 0))->pluck('id')->all();
        $this->mediaUsages()->get()->each(function (MediaUsage $usage) use ($wanted) {
            if (! in_array([(int) $usage->media_id, $usage->context], $wanted, true)) {
                $usage->delete();
            }
        });
        foreach ($wanted as [$id, $context]) {
            if (in_array($id, $existing, true)) {
                $this->mediaUsages()->firstOrCreate(['media_id' => $id, 'context' => $context]);
            }
        }
    }
}

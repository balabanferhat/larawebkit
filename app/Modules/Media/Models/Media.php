<?php

namespace App\Modules\Media\Models;

use App\Models\User;
use App\Modules\Logs\Concerns\Auditable;
use App\Modules\Media\ImagePipeline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property array<string, array{path: string, width: int, height: int, size: int}>|null $variants
 */
class Media extends Model
{
    use Auditable;

    protected $table = 'media';

    protected $guarded = ['id'];

    protected array $auditExclude = ['variants'];

    protected function casts(): array
    {
        return ['variants' => 'array', 'width' => 'integer', 'height' => 'integer', 'size' => 'integer'];
    }

    protected static function booted(): void
    {
        static::deleting(function (Media $media) {
            app(ImagePipeline::class)->deleteFiles($media);
        });
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(MediaUsage::class);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $term === '' ? $query : $query->where(fn ($q) => $q
            ->where('file_name', 'like', "%$term%")
            ->orWhere('title', 'like', "%$term%")
            ->orWhere('alt', 'like', "%$term%"));
    }

    public function auditLabel(): string
    {
        return $this->title ?: $this->file_name;
    }

    /** Sorted width variants (excludes the square thumbnail). */
    public function widthVariants(): array
    {
        $variants = array_filter($this->variants ?? [], fn ($v, $k) => is_numeric($k), ARRAY_FILTER_USE_BOTH);
        ksort($variants, SORT_NUMERIC);

        return $variants;
    }

    /** @return array{path: string, width: int, height: int, size: int}|null */
    public function variant(string|int $size = 'max'): ?array
    {
        $variants = $this->variants ?? [];
        if ($size === 'thumb') {
            return $variants['thumb'] ?? $this->variant(640);
        }
        $widths = $this->widthVariants();
        if ($widths === []) {
            return null;
        }
        if ($size === 'max') {
            return end($widths);
        }
        // Smallest variant that is at least $size wide, else the largest.
        foreach ($widths as $width => $variant) {
            if ((int) $width >= (int) $size) {
                return $variant;
            }
        }

        return end($widths);
    }

    public function url(string|int $size = 'max'): ?string
    {
        $variant = $this->variant($size);

        return $variant ? Storage::disk($this->disk)->url($variant['path']) : null;
    }

    public function srcset(): string
    {
        return collect($this->widthVariants())
            ->map(fn ($v) => Storage::disk($this->disk)->url($v['path']).' '.$v['width'].'w')
            ->implode(', ');
    }

    public function isStock(): bool
    {
        return $this->source === 'pexels';
    }

    /** Pages + posts that reference this media item (featured or inline). */
    public function usageCount(): int
    {
        return $this->usages()->get(['usable_type', 'usable_id'])
            ->unique(fn (MediaUsage $u) => $u->usable_type.':'.$u->usable_id)
            ->count();
    }
}

<?php

namespace App\Modules\Content\Models;

use App\Modules\Content\Concerns\HasContent;
use App\Modules\Content\Concerns\HasRevisions;
use App\Modules\Logs\Concerns\Auditable;
use App\Modules\Media\Concerns\TracksMediaUsage;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Hierarchical page: its path is the parent's path plus its own slug,
 * e.g. /about/team. Template comes from the active theme.
 */
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use Auditable, HasContent, HasFactory, HasRevisions, SoftDeletes, TracksMediaUsage;

    public const TEMPLATES = ['default', 'full-width', 'landing', 'contact', 'legal'];

    protected $guarded = ['id', 'path', 'seo_score', 'human_score', 'geo_score', 'word_count', 'announced_at'];

    protected $attributes = ['status' => 'draft', 'template' => 'default'];

    /** Slugs are unique among siblings only. */
    protected string $slugScope = 'parent_id';

    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function beforeContentSave(): void
    {
        if ($this->parent_id && $this->parent_id === $this->getKey()) {
            $this->parent_id = null;
        }
        $this->path = $this->computePath();
    }

    public function computePath(): string
    {
        $segments = [$this->slug];
        $parent = $this->parent;
        $guard = 0;
        while ($parent && $guard++ < 10) {
            array_unshift($segments, $parent->slug);
            $parent = $parent->parent;
        }

        return implode('/', $segments);
    }

    public function publicPath(): string
    {
        return localized_path('/'.$this->path, $this->locale);
    }

    protected function previousPublicPath(): ?string
    {
        if (! $this->wasChanged(['path', 'locale'])) {
            return null;
        }

        return localized_path('/'.$this->getOriginal('path'), $this->getOriginal('locale'));
    }

    protected static function booted(): void
    {
        // Children follow when a parent's path changes.
        static::updated(function (Page $page) {
            if ($page->wasChanged('path')) {
                $page->children->each(fn (Page $child) => $child->save());
            }
        });
    }

    /** @return list<self> root → … → parent */
    public function ancestors(): array
    {
        $out = [];
        $parent = $this->parent;
        while ($parent && count($out) < 10) {
            array_unshift($out, $parent);
            $parent = $parent->parent;
        }

        return $out;
    }
}

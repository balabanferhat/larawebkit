<?php

namespace App\Modules\Content\Models;

use App\Modules\Blocks\BlockRegistry;
use App\Modules\Content\Concerns\HasContent;
use App\Modules\Content\Concerns\HasRevisions;
use App\Modules\Core\ModuleRegistry;
use App\Modules\Editor\Scoring\TextAnalyzer;
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
 * e.g. /about/team. Template comes from the active theme. A page can be
 * built from section blocks (`blocks`, see App\Modules\Blocks).
 */
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use Auditable, HasContent, HasFactory, SoftDeletes, TracksMediaUsage;

    use HasRevisions {
        revisionFields as traitRevisionFields;
    }

    public const TEMPLATES = ['default', 'full-width', 'landing', 'contact', 'legal'];

    protected $guarded = ['id', 'path', 'seo_score', 'human_score', 'geo_score', 'word_count', 'announced_at'];

    protected $attributes = ['status' => 'draft', 'template' => 'default'];

    /** Slugs are unique among siblings only. */
    protected string $slugScope = 'parent_id';

    protected function casts(): array
    {
        return ['blocks' => 'array'];
    }

    public function hasBlocks(): bool
    {
        return ModuleRegistry::enabled('blocks') && ! empty($this->blocks);
    }

    /** @return list<array{type: string, data: array<string, mixed>}> */
    public function blockList(): array
    {
        return $this->hasBlocks() ? app(BlockRegistry::class)->normalize($this->blocks) : [];
    }

    /** Page FAQ plus the items of FAQ blocks (FAQPage schema, Markdown twin). */
    public function faqItems(): array
    {
        return [...(array) ($this->faq ?? []), ...($this->hasBlocks() ? app(BlockRegistry::class)->faq($this->blocks) : [])];
    }

    /** Text of the section blocks as Markdown (Markdown twin, llms-full.txt, word count). */
    public function blocksText(bool $withFaq = true): string
    {
        return $this->hasBlocks() ? app(BlockRegistry::class)->text($this->blocks, $withFaq ? [] : ['faq']) : '';
    }

    /** @return list<int> */
    public function extraMediaIds(): array
    {
        return $this->hasBlocks() ? app(BlockRegistry::class)->mediaIds($this->blocks) : [];
    }

    public function revisionFields(): array
    {
        return [...$this->traitRevisionFields(), 'blocks'];
    }

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
        // Block text counts towards the word count (scores, publish gate, reading time).
        static::saving(function (Page $page) {
            if ($text = $page->blocksText()) {
                $page->word_count += count(TextAnalyzer::words($text));
            }
        });

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

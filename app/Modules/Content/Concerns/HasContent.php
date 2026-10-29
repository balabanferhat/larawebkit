<?php

namespace App\Modules\Content\Concerns;

use App\Models\User;
use App\Modules\Content\Events\ContentPublished;
use App\Modules\Content\Events\ContentSaved;
use App\Modules\Content\Models\Redirect;
use App\Modules\Content\Support\HtmlSanitizer;
use App\Modules\Editor\Scoring\TextAnalyzer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Shared behaviour of pages and posts: statuses and scheduling, sanitized
 * HTML, unique slugs, automatic 301 on URL change, publish events and SEO
 * fallbacks. Recipe for a new content type: docs/recipes/new-content-type.md
 *
 * @property string $status
 * @property ?Carbon $published_at
 */
trait HasContent
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    /** Public path of this item, e.g. "/blog/hello-world". */
    abstract public function publicPath(): string;

    /** @return list<array{question: string, answer: string}> FAQ items for FAQPage schema and the Markdown twin */
    public function faqItems(): array
    {
        return array_values(array_filter((array) ($this->faq ?? []), fn ($i) => filled($i['question'] ?? null) && filled($i['answer'] ?? null)));
    }

    public function initializeHasContent(): void
    {
        $this->mergeCasts([
            'keywords' => 'array',
            'faq' => 'array',
            'published_at' => 'datetime',
            'announced_at' => 'datetime',
            'noindex' => 'boolean',
            'seo_score' => 'integer',
            'human_score' => 'integer',
            'geo_score' => 'integer',
            'word_count' => 'integer',
        ]);
    }

    public static function bootHasContent(): void
    {
        static::saving(function (Model $model) {
            $model->content = app(HtmlSanitizer::class)->sanitize($model->content);
            $model->word_count = count(TextAnalyzer::words(TextAnalyzer::htmlToText((string) $model->content)));
            $model->locale = $model->locale ?: (string) setting('general.default_locale', 'en');
            $model->slug = $model->uniqueSlug($model->slug ?: Str::slug((string) $model->title) ?: Str::lower(Str::random(8)));
            $model->translation_group ??= (string) Str::uuid();
            if ($model->status === self::STATUS_PUBLISHED && ! $model->published_at) {
                $model->published_at = now();
            }
            if (method_exists($model, 'beforeContentSave')) {
                $model->beforeContentSave();
            }
        });

        static::updated(function (Model $model) {
            $model->redirectOldPath();
        });

        static::saved(function (Model $model) {
            ContentSaved::dispatch($model);
            $model->announceIfVisible();
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)->where('published_at', '<=', now());
    }

    public function scopeIndexable(Builder $query): Builder
    {
        return $query->published()->where('noindex', false);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->published_at && $this->published_at->lte(now());
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->published_at && $this->published_at->isFuture();
    }

    /** draft | pending | scheduled | published */
    public function effectiveStatus(): string
    {
        return $this->isScheduled() ? 'scheduled' : (string) $this->status;
    }

    public function url(): string
    {
        return url($this->publicPath());
    }

    public function seoTitle(): string
    {
        return (string) ($this->meta_title ?: $this->title);
    }

    public function seoDescription(): string
    {
        // Members-only text never becomes a public description (meta, cards, feeds, search results).
        $body = ! empty($this->members_only) ? '' : TextAnalyzer::htmlToText((string) $this->content);
        $text = $this->meta_description ?: ($this->excerpt ?? null) ?: $this->summary ?: $body;

        return Str::limit(trim(preg_replace('/\s+/', ' ', (string) $text)), 160, '…');
    }

    /** Other-locale versions of this item (for hreflang). */
    public function translations(): Collection
    {
        if (! $this->translation_group) {
            return collect();
        }

        return static::query()->published()->where('translation_group', $this->translation_group)->whereKeyNot($this->getKey())->get();
    }

    public function auditLabel(): string
    {
        return (string) $this->title;
    }

    /** Fire ContentPublished once, when the item becomes publicly visible. */
    public function announceIfVisible(): void
    {
        if ($this->isPublished() && ! $this->announced_at) {
            $this->forceFill(['announced_at' => now()])->saveQuietly();
            ContentPublished::dispatch($this);
        }
    }

    protected function uniqueSlug(string $slug): string
    {
        $slug = Str::slug($slug) ?: Str::lower(Str::random(8));
        $base = $slug;
        $i = 2;
        while ($this->slugTaken($slug)) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    protected function slugTaken(string $slug): bool
    {
        return static::withoutGlobalScopes()
            ->where('locale', $this->locale)
            ->where('slug', $slug)
            ->when(property_exists($this, 'slugScope'), fn ($q) => $q->where($this->slugScope, $this->{$this->slugScope}))
            ->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()))
            ->exists();
    }

    /** Automatic 301 when the public URL of a published item changes. */
    protected function redirectOldPath(): void
    {
        $oldPath = $this->previousPublicPath();
        if ($oldPath === null || $this->getOriginal('status') !== self::STATUS_PUBLISHED) {
            return;
        }
        $newPath = $this->publicPath();
        if ($oldPath === $newPath) {
            return;
        }

        Redirect::where('from_path', $newPath)->delete();
        Redirect::updateOrCreate(['from_path' => $oldPath], ['to_url' => $newPath, 'status_code' => 301, 'is_automatic' => true]);
        // Collapse chains: anything pointing at the old path now points at the new one.
        Redirect::where('to_url', $oldPath)->update(['to_url' => $newPath]);
    }

    /** The public path before the current update, or null when it did not change. */
    abstract protected function previousPublicPath(): ?string;
}

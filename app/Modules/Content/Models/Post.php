<?php

namespace App\Modules\Content\Models;

use App\Modules\Content\Concerns\HasContent;
use App\Modules\Content\Concerns\HasRevisions;
use App\Modules\Logs\Concerns\Auditable;
use App\Modules\Media\Concerns\TracksMediaUsage;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use Auditable, HasContent, HasFactory, HasRevisions, SoftDeletes, TracksMediaUsage;

    protected $guarded = ['id', 'seo_score', 'human_score', 'geo_score', 'word_count', 'announced_at'];

    protected $attributes = ['status' => 'draft'];

    protected function casts(): array
    {
        return ['members_only' => 'boolean'];
    }

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function publicPath(): string
    {
        return localized_path('/blog/'.$this->slug, $this->locale);
    }

    protected function previousPublicPath(): ?string
    {
        if (! $this->wasChanged(['slug', 'locale'])) {
            return null;
        }

        return localized_path('/blog/'.$this->getOriginal('slug'), $this->getOriginal('locale'));
    }

    /** Published posts sharing a category or tag, newest first. */
    public function related(int $limit = 3)
    {
        $categoryIds = $this->categories()->pluck('categories.id');
        $tagIds = $this->tags()->pluck('tags.id');

        $related = static::query()->published()
            ->where('locale', $this->locale)
            ->whereKeyNot($this->getKey())
            ->where(fn ($q) => $q
                ->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $categoryIds))
                ->orWhereHas('tags', fn ($t) => $t->whereIn('tags.id', $tagIds)))
            ->with('featuredMedia', 'author')
            ->latest('published_at')
            ->limit($limit)
            ->get();
        if ($related->count() >= $limit) {
            return $related;
        }

        // Too few in the same topics: fill up with the latest posts.
        return $related->concat(static::query()->published()->where('locale', $this->locale)
            ->whereKeyNot([$this->getKey(), ...$related->modelKeys()])
            ->with('featuredMedia', 'author')->latest('published_at')->limit($limit - $related->count())->get());
    }

    /** @return array{previous: ?self, next: ?self} the published posts just before and after this one */
    public function adjacent(): array
    {
        if (! $this->published_at) {
            return ['previous' => null, 'next' => null];
        }
        $base = fn () => static::query()->published()->where('locale', $this->locale)->whereKeyNot($this->getKey());

        return [
            'previous' => $base()->where(fn ($q) => $q->where('published_at', '<', $this->published_at)
                ->orWhere(fn ($w) => $w->where('published_at', $this->published_at)->where('id', '<', $this->id)))
                ->orderByDesc('published_at')->orderByDesc('id')->first(['id', 'title', 'slug', 'locale', 'published_at']),
            'next' => $base()->where(fn ($q) => $q->where('published_at', '>', $this->published_at)
                ->orWhere(fn ($w) => $w->where('published_at', $this->published_at)->where('id', '>', $this->id)))
                ->orderBy('published_at')->orderBy('id')->first(['id', 'title', 'slug', 'locale', 'published_at']),
        ];
    }
}

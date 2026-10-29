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

        return static::query()->published()
            ->where('locale', $this->locale)
            ->whereKeyNot($this->getKey())
            ->where(fn ($q) => $q
                ->whereHas('categories', fn ($c) => $c->whereIn('categories.id', $categoryIds))
                ->orWhereHas('tags', fn ($t) => $t->whereIn('tags.id', $tagIds)))
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }
}

<?php

namespace App\Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MenuItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['new_tab' => 'boolean'];
    }

    protected static function booted(): void
    {
        // Dropdown entries are saved through the parent item: inherit its menu.
        static::saving(function (MenuItem $item) {
            if ($item->parent_id && ! $item->menu_id) {
                $item->menu_id = static::whereKey($item->parent_id)->value('menu_id');
            }
        });
        static::deleting(fn (MenuItem $item) => $item->children()->get()->each->delete());
        static::saved(fn () => Menu::flush());
        static::deleted(fn () => Menu::flush());
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    public function href(): ?string
    {
        if ($this->linkable) {
            return method_exists($this->linkable, 'isPublished') && ! $this->linkable->isPublished() ? null : $this->linkable->publicPath();
        }

        return $this->url ?: null;
    }
}

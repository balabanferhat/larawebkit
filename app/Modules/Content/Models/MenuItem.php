<?php

namespace App\Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        static::saved(fn () => Menu::flush());
        static::deleted(fn () => Menu::flush());
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
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

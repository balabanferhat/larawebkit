<?php

namespace App\Modules\Content\Models;

use App\Modules\Logs\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Category extends Model
{
    use Auditable;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(fn (Category $c) => $c->slug = Str::slug($c->slug ?: $c->name));
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    public function publicPath(): string
    {
        return localized_path('/blog/category/'.$this->slug);
    }

    public function auditLabel(): string
    {
        return $this->name;
    }
}

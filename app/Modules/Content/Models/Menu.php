<?php

namespace App\Modules\Content\Models;

use App\Modules\Logs\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Menu extends Model
{
    use Auditable;

    public const LOCATIONS = ['header', 'footer'];

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(fn () => self::flush());
        static::deleted(fn () => self::flush());
    }

    public static function flush(): void
    {
        foreach (self::LOCATIONS as $location) {
            Cache::forget("larawebkit.menu.$location");
        }
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }

    /** @return Collection<int, array{label: string, url: string, new_tab: bool}> */
    public static function for(string $location): Collection
    {
        $items = Cache::rememberForever("larawebkit.menu.$location", function () use ($location) {
            $menu = static::with('items.linkable')->where('location', $location)->first();

            return $menu ? $menu->items->map(fn (MenuItem $item) => [
                'label' => $item->label,
                'url' => $item->href(),
                'new_tab' => $item->new_tab,
            ])->filter(fn ($i) => $i['url'] !== null)->values()->all() : [];
        });

        return collect($items);
    }

    public function auditLabel(): string
    {
        return $this->name;
    }
}

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

    /** header (with dropdowns), footer columns and the small legal links row. */
    public const LOCATIONS = ['header', 'footer', 'footer_secondary', 'legal'];

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
            Cache::forget("larawebkit.menu.$location.name");
        }
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }

    /** Top-level items; their children are the dropdown entries. */
    public function rootItems(): HasMany
    {
        return $this->items()->whereNull('parent_id');
    }

    /**
     * The menu at a location as a tree (two levels: items and their dropdown children).
     * An item whose page/post is unpublished is hidden; a parent without a link stays
     * when it has children (it only opens the dropdown).
     *
     * @return Collection<int, array{label: string, url: ?string, new_tab: bool, children: list<array{label: string, url: string, new_tab: bool}>}>
     */
    public static function for(string $location): Collection
    {
        $items = Cache::rememberForever("larawebkit.menu.$location", function () use ($location) {
            $menu = static::with('items.linkable')->where('location', $location)->first();
            if (! $menu) {
                return [];
            }
            $children = $menu->items->whereNotNull('parent_id')->groupBy('parent_id');
            $node = fn (MenuItem $item) => ['label' => $item->label, 'url' => $item->href(), 'new_tab' => $item->new_tab];

            return $menu->items->whereNull('parent_id')->map(function (MenuItem $item) use ($children, $node) {
                $kids = collect($children->get($item->id, []))->map($node)->filter(fn ($c) => $c['url'] !== null)->values()->all();

                return $node($item) + ['children' => $kids];
            })->filter(fn ($i) => $i['url'] !== null || $i['children'] !== [])->values()->all();
        });

        return collect($items);
    }

    public static function label(string $location): ?string
    {
        return Cache::rememberForever("larawebkit.menu.$location.name", fn () => static::where('location', $location)->value('name') ?? '') ?: null;
    }

    public function auditLabel(): string
    {
        return $this->name;
    }
}

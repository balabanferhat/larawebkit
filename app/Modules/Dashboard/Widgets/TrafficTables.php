<?php

namespace App\Modules\Dashboard\Widgets;

use App\Modules\Dashboard\Analytics;
use Filament\Widgets\Widget;

/** Top pages and referrers (AI assistants highlighted) from the cookieless access log. */
class TrafficTables extends Widget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'admin.widgets.traffic';

    public static function canView(): bool
    {
        return auth()->user()?->can('view dashboard') ?? false;
    }

    protected function getViewData(): array
    {
        return ['pages' => Analytics::topPages(), 'referrers' => Analytics::referrers()];
    }
}

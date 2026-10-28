<?php

namespace App\Modules\Dashboard;

use App\Modules\Dashboard\Widgets\ContentAttention;
use App\Modules\Dashboard\Widgets\RecentActivity;
use App\Modules\Dashboard\Widgets\StatsOverview;
use App\Modules\Dashboard\Widgets\TrafficTables;
use App\Modules\Dashboard\Widgets\ViewsChart;
use Filament\Contracts\Plugin;
use Filament\Pages\Dashboard;
use Filament\Panel;

class DashboardPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'larawebkit-dashboard';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([Dashboard::class])->widgets([
            StatsOverview::class, ViewsChart::class, TrafficTables::class, ContentAttention::class, RecentActivity::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}

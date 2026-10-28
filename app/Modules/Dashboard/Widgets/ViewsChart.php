<?php

namespace App\Modules\Dashboard\Widgets;

use App\Modules\Dashboard\Analytics;
use App\Modules\Logs\Support\AgentClassifier;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ViewsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '260px';

    public function getHeading(): ?string
    {
        return __('larawebkit-dashboard.daily_views');
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view dashboard') ?? false;
    }

    protected function getData(): array
    {
        $views = Analytics::dailyViews(30);
        $bots = Analytics::dailyViews(30, AgentClassifier::AI_BOT);

        return [
            'datasets' => [
                ['label' => __('larawebkit-dashboard.visitors'), 'data' => array_values($views), 'borderColor' => '#4f46e5', 'backgroundColor' => 'rgba(79,70,229,.1)', 'fill' => true, 'tension' => 0.3],
                ['label' => __('larawebkit-dashboard.ai_bots'), 'data' => array_values($bots), 'borderColor' => '#0d9488', 'tension' => 0.3],
            ],
            'labels' => array_map(fn ($d) => Carbon::parse($d)->isoFormat('D MMM'), array_keys($views)),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

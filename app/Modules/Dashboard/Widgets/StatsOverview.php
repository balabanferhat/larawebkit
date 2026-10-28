<?php

namespace App\Modules\Dashboard\Widgets;

use App\Modules\Ai\AiManager;
use App\Modules\Ai\Models\AiUsage;
use App\Modules\Content\Models\Post;
use App\Modules\Dashboard\Analytics;
use App\Modules\Logs\Models\AuthLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->can('view dashboard') ?? false;
    }

    protected function getStats(): array
    {
        $views = Analytics::dailyViews(30);
        $failed = AuthLog::whereIn('event', ['failed', 'lockout', 'two_factor_failed'])->where('created_at', '>=', now()->subDays(7))->count();
        $budget = (float) setting('ai.monthly_budget_usd', 0);
        $spent = AiUsage::spentThisMonth();

        $stats = [
            Stat::make(__('larawebkit-dashboard.views_30'), number_format(array_sum($views)))
                ->description(__('larawebkit-dashboard.unique', ['count' => number_format(Analytics::uniqueVisitors(30))]))
                ->chart(array_values($views))->color('primary'),
            Stat::make(__('larawebkit-dashboard.failed_logins'), (string) $failed)
                ->description(__('larawebkit-dashboard.last_7_days'))->color($failed > 10 ? 'danger' : 'gray'),
            Stat::make(__('larawebkit-dashboard.drafts'), (string) Post::whereIn('status', ['draft', 'pending'])->count())
                ->description(__('larawebkit-dashboard.scheduled', ['count' => Post::where('status', 'published')->where('published_at', '>', now())->count()])),
        ];

        if (app(AiManager::class)->enabled()) {
            $stats[] = Stat::make(__('larawebkit-dashboard.ai_spend'), '$'.number_format($spent, 2))
                ->description($budget > 0 ? __('larawebkit-dashboard.ai_budget', ['budget' => number_format($budget, 2), 'percent' => (int) min(100, round($spent / $budget * 100))]) : __('larawebkit-dashboard.ai_no_budget'))
                ->color($budget > 0 && $spent >= $budget ? 'danger' : 'success');
        }

        return $stats;
    }
}

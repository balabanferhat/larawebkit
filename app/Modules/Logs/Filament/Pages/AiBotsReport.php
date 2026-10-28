<?php

namespace App\Modules\Logs\Filament\Pages;

use App\Modules\Logs\Models\AccessLog;
use App\Modules\Logs\Support\AgentClassifier;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * GEO report: which AI bots crawl which pages (and when they were last seen),
 * plus visits referred by AI assistants.
 */
class AiBotsReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?int $navigationSort = 83;

    protected string $view = 'admin.ai-bots-report';

    public int $days = 30;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.logs');
    }

    public static function getNavigationLabel(): string
    {
        return __('larawebkit-logs.ai_report.title');
    }

    public function getTitle(): string
    {
        return __('larawebkit-logs.ai_report.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view logs') ?? false;
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return self::report($this->days);
    }

    /** @return array{bots: array, pages: array, referrals: array} */
    public static function report(int $days = 30): array
    {
        $since = now()->subDays($days);
        $base = fn () => AccessLog::query()->where('created_at', '>=', $since);

        $bots = $base()->where('agent_class', AgentClassifier::AI_BOT)
            ->select('bot_name', DB::raw('count(*) as hits'), DB::raw('count(distinct path) as pages'), DB::raw('max(created_at) as last_seen'))
            ->groupBy('bot_name')->orderByDesc('hits')->get()->toArray();

        $pages = $base()->where('agent_class', AgentClassifier::AI_BOT)
            ->select('bot_name', 'path', DB::raw('count(*) as hits'), DB::raw('max(created_at) as last_seen'))
            ->groupBy('bot_name', 'path')->orderByDesc('hits')->limit(50)->get()->toArray();

        $hosts = array_keys((array) config('larawebkit-geo.ai_referrers'));
        $referrals = $base()->whereIn('referrer_host', $hosts)
            ->select('referrer_host', DB::raw('count(*) as visits'), DB::raw('max(created_at) as last_seen'))
            ->groupBy('referrer_host')->orderByDesc('visits')->get()
            ->map(fn ($row) => [...$row->toArray(), 'assistant' => config('larawebkit-geo.ai_referrers')[$row->referrer_host] ?? $row->referrer_host])
            ->all();

        return compact('bots', 'pages', 'referrals');
    }
}

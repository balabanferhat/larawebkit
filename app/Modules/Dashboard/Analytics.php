<?php

namespace App\Modules\Dashboard;

use App\Modules\Logs\Models\AccessLog;
use App\Modules\Logs\Support\AgentClassifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Cookieless analytics from the access log (human visits only unless stated). */
class Analytics
{
    /** @return array<string, int> Y-m-d => views */
    public static function dailyViews(int $days = 30, string $class = AgentClassifier::HUMAN): array
    {
        $since = now()->subDays($days - 1)->startOfDay();
        $rows = AccessLog::query()->where('agent_class', $class)->where('status', '<', 400)->where('created_at', '>=', $since)
            ->select('day', DB::raw('count(*) as views'))->groupBy('day')->pluck('views', 'day')
            ->mapWithKeys(fn ($v, $k) => [Carbon::parse($k)->toDateString() => (int) $v]);

        $out = [];
        for ($d = $since->copy(); $d->lte(now()); $d->addDay()) {
            $out[$d->toDateString()] = $rows[$d->toDateString()] ?? 0;
        }

        return $out;
    }

    public static function uniqueVisitors(int $days = 30): int
    {
        // ip_hash rotates daily, so this counts "daily unique visitors" summed over the period.
        return (int) AccessLog::query()->where('agent_class', AgentClassifier::HUMAN)->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('ip_hash')->distinct()->count('ip_hash');
    }

    /** @return list<array{path: string, views: int}> */
    public static function topPages(int $days = 30, int $limit = 10): array
    {
        return AccessLog::query()->where('agent_class', AgentClassifier::HUMAN)->where('status', 200)->where('created_at', '>=', now()->subDays($days))
            ->select('path', DB::raw('count(*) as views'))->groupBy('path')->orderByDesc('views')->limit($limit)->get()
            ->map(fn ($r) => ['path' => $r->path, 'views' => (int) $r->views])->all();
    }

    /** @return list<array{host: string, visits: int, ai: ?string}> */
    public static function referrers(int $days = 30, int $limit = 10): array
    {
        $ai = (array) config('larawebkit-geo.ai_referrers');

        return AccessLog::query()->where('agent_class', AgentClassifier::HUMAN)->whereNotNull('referrer_host')->where('created_at', '>=', now()->subDays($days))
            ->select('referrer_host', DB::raw('count(*) as visits'))->groupBy('referrer_host')->orderByDesc('visits')->limit($limit)->get()
            ->map(fn ($r) => ['host' => $r->referrer_host, 'visits' => (int) $r->visits, 'ai' => $ai[$r->referrer_host] ?? null])->all();
    }
}

<?php

namespace App\Modules\Logs\Http\Middleware;

use App\Modules\Logs\Models\AccessLog;
use App\Modules\Logs\Support\AgentClassifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Cookieless access log for front-end requests. The IP is never stored:
 * only a keyed hash that rotates daily (enough to count unique visitors per day).
 */
class LogAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! setting('features.access_log', true) || $this->skip($request)) {
            return;
        }

        try {
            $agent = AgentClassifier::classify($request->userAgent());
            $referrer = (string) $request->headers->get('referer', '');
            $referrerHost = $referrer !== '' ? parse_url($referrer, PHP_URL_HOST) : null;
            if ($referrerHost === $request->getHost()) {
                $referrerHost = null;
                $referrer = '';
            }
            $start = defined('LARAVEL_START') ? LARAVEL_START : $request->server('REQUEST_TIME_FLOAT', microtime(true));

            AccessLog::create([
                'day' => now()->toDateString(),
                'method' => $request->method(),
                'path' => mb_substr('/'.ltrim($request->path(), '/'), 0, 500),
                'status' => $response->getStatusCode(),
                'referrer_host' => $referrerHost ? mb_substr(strtolower($referrerHost), 0, 255) : null,
                'referrer' => $referrer !== '' ? mb_substr(strtok($referrer, '?') ?: $referrer, 0, 500) : null,
                'agent_class' => $agent['class'],
                'bot_name' => $agent['bot'],
                'ip_hash' => self::hashIp((string) $request->ip()),
                'duration_ms' => (int) max(0, round((microtime(true) - (float) $start) * 1000)),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public static function hashIp(string $ip): string
    {
        return hash_hmac('sha256', $ip.'|'.now()->toDateString(), (string) config('app.key'));
    }

    private function skip(Request $request): bool
    {
        $admin = trim((string) config('larawebkit.admin_path', 'admin'), '/');

        return $request->is($admin, $admin.'/*', 'livewire*', 'filament/*', 'build/*', 'storage/*', 'up', 'css/*', 'js/*', 'fonts/*', 'favicon.ico')
            || ! in_array($request->method(), ['GET', 'HEAD'], true);
    }
}

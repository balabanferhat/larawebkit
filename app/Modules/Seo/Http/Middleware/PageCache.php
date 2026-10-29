<?php

namespace App\Modules\Seo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Full-page cache for anonymous GET requests (file cache, works on shared
 * hosting). The whole cache "generation" is bumped on every publish/save, so
 * stale pages are never served after an edit.
 */
class PageCache
{
    public const VERSION_KEY = 'larawebkit.page_cache.version';

    public static function bust(): void
    {
        Cache::store(self::store())->forever(self::VERSION_KEY, (string) microtime(true));
    }

    public static function store(): string
    {
        return (string) config('larawebkit-seo.page_cache.store', 'file');
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->cacheable($request)) {
            return $next($request);
        }

        $cache = Cache::store(self::store());
        $version = $cache->get(self::VERSION_KEY, '0');
        $key = 'larawebkit.page:'.sha1($version.'|'.$request->getHost().'|'.$request->getRequestUri().'|'.$request->header('Accept'));

        if (is_array($hit = $cache->get($key))) {
            return (new IlluminateResponse($hit['body'], 200, $hit['headers']))->header('X-Page-Cache', 'HIT');
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200 && $this->storable($response)) {
            $cache->put($key, [
                'body' => $response->getContent(),
                'headers' => ['Content-Type' => $response->headers->get('Content-Type'), 'Link' => $response->headers->get('Link'), 'X-Robots-Tag' => $response->headers->get('X-Robots-Tag')],
            ], (int) config('larawebkit-seo.page_cache.ttl', 3600));
            $response->headers->set('X-Page-Cache', 'MISS');
        }

        return $response;
    }

    private function cacheable(Request $request): bool
    {
        if (! setting('features.page_cache', true) || ! $request->isMethod('GET') || $request->query->count() > 0 && ! $request->query->has('page')) {
            return false;
        }
        if ($request->user() || $request->hasSession() && $request->session()->has('_flash.new') && $request->session()->get('_flash.new') !== []) {
            return false;
        }
        // Visitors with a session cookie from a form submission get a fresh page (CSRF tokens, flash messages).
        if ($request->cookies->has('lwk_no_cache')) {
            return false;
        }
        $admin = trim((string) config('larawebkit.admin_path', 'admin'), '/');

        return ! $request->is($admin, $admin.'/*', 'livewire*', '_preview/*', 'search', '*/search', 'newsletter/*', 'forms/*', 'download', 'account', 'account/*');
    }

    private function storable(Response $response): bool
    {
        return ! str_contains((string) $response->headers->get('Cache-Control'), 'no-store')
            && ! str_contains((string) $response->getContent(), 'name="_token"');
    }
}

<?php

namespace App\Modules\Seo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * One canonical host: 301 to https, to the canonical host / www policy and
 * to the trailing-slash policy. Admin and Livewire requests are left alone.
 */
class CanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true) || app()->runningUnitTests() && ! config('larawebkit-seo.enforce_in_tests')) {
            return $next($request);
        }
        $admin = trim((string) config('larawebkit.admin_path', 'admin'), '/');
        if ($request->is($admin, $admin.'/*', 'livewire*', 'up')) {
            return $next($request);
        }

        $scheme = $request->getScheme();
        $host = $request->getHost();
        $path = $request->getPathInfo();

        if (setting('seo.force_https', false)) {
            $scheme = 'https';
        }
        $canonicalHost = (string) setting('seo.canonical_host', '');
        if ($canonicalHost !== '') {
            $host = $canonicalHost;
        } else {
            $host = match (setting('seo.www_policy', 'ignore')) {
                'www' => str_starts_with($host, 'www.') ? $host : 'www.'.$host,
                'non-www' => preg_replace('/^www\./', '', $host),
                default => $host,
            };
        }
        if ($path !== '/' && ! str_contains(basename($path), '.')) {
            $path = match (setting('seo.trailing_slash', 'remove')) {
                'remove' => rtrim($path, '/'),
                'add' => rtrim($path, '/').'/',
                default => $path,
            };
        }

        if ($scheme !== $request->getScheme() || $host !== $request->getHost() || $path !== $request->getPathInfo()) {
            $port = $request->getPort();
            $portPart = in_array($port, [80, 443], true) || $host !== $request->getHost() ? '' : ':'.$port;
            $query = $request->getQueryString();

            return redirect()->to("$scheme://$host$portPart$path".($query ? "?$query" : ''), 301);
        }

        return $next($request);
    }
}

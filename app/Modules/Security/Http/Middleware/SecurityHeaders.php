<?php

namespace App\Modules\Security\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach ((array) config('larawebkit-security.headers', []) as $name => $value) {
            if ($value !== null && ! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        $maxAge = (int) config('larawebkit-security.hsts_max_age', 0);
        if ($maxAge > 0 && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', "max-age=$maxAge; includeSubDomains");
        }

        $admin = trim((string) config('larawebkit.admin_path', 'admin'), '/');
        $csp = config('larawebkit-security.front_csp');
        if (filled($csp) && ! $request->is($admin, $admin.'/*', 'livewire*')) {
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}

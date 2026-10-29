<?php

namespace App\Modules\Content\Http\Middleware;

use App\Modules\Content\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Applies admin-managed redirects (301/302) and 410 Gone before routing to content. */
class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $redirect = Redirect::lookup('/'.ltrim($request->path(), '/'));
        if ($redirect === null) {
            return $next($request);
        }

        Redirect::whereKey($redirect['id'])->update(['hits' => \DB::raw('hits + 1'), 'last_hit_at' => now()]);

        if ($redirect['status'] === 410 || blank($redirect['to'])) {
            abort(410);
        }

        $target = $redirect['to'];
        if ($request->getQueryString() && ! str_contains($target, '?')) {
            $target .= '?'.$request->getQueryString();
        }

        return redirect()->to($target, $redirect['status'] === 302 ? 302 : 301);
    }
}

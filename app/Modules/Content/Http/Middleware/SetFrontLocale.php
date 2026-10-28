<?php

namespace App\Modules\Content\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default locale has no URL prefix; other active locales use /{locale}/...
 * A prefix for the default or an inactive locale is a 404.
 */
class SetFrontLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $default = (string) setting('general.default_locale', 'en');
        $active = (array) setting('general.active_locales', [$default]);
        $locale = $request->route('locale');

        if ($locale !== null) {
            abort_if($locale === $default || ! in_array($locale, $active, true), 404);
            $request->route()->forgetParameter('locale');
        }

        app()->setLocale($locale ?? $default);

        return $next($request);
    }
}

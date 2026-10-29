<?php

namespace App\Modules\Events\Http;

use App\Modules\Events\Events;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The event pages are a 404 while Settings → Features → Events is off. */
class EnsureEventsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Events::enabled(), 404);

        return $next($request);
    }
}

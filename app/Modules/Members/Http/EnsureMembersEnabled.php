<?php

namespace App\Modules\Members\Http;

use App\Modules\Members\Members;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The member area only exists while Settings → Features → Members is on. */
class EnsureMembersEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Members::enabled(), 404);

        return $next($request);
    }
}

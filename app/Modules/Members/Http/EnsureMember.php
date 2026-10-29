<?php

namespace App\Modules\Members\Http;

use App\Modules\Members\Members;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Member area: must be logged in as a member; "verified" also requires a confirmed e-mail address. */
class EnsureMember
{
    public function handle(Request $request, Closure $next, ?string $verified = null): Response
    {
        $member = Members::current();
        if (! $member || ! $member->is_active) {
            return redirect()->guest(route('members.login'));
        }
        if ($verified === 'verified' && ! $member->hasVerifiedEmail()) {
            return redirect()->route('members.verification.notice');
        }

        return $next($request);
    }
}

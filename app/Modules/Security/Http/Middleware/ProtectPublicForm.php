<?php

namespace App\Modules\Security\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Honeypot + minimum fill time + per-IP rate limit for public forms.
 * Render the fields with <x-lwk.honeypot /> inside the form.
 */
class ProtectPublicForm
{
    public function handle(Request $request, Closure $next): Response
    {
        $config = (array) config('larawebkit-security.honeypot');

        if (filled($request->input($config['field']))) {
            return $this->reject();
        }

        try {
            $time = (int) Crypt::decryptString((string) $request->input($config['time_field']));
        } catch (DecryptException) {
            return $this->reject();
        }
        if (time() - $time < (int) $config['min_seconds']) {
            return $this->reject();
        }

        $key = 'public-form:'.$request->route()?->getName().':'.$request->ip();
        $limit = (int) config('larawebkit-security.public_form_rate_limit', 5);
        if (RateLimiter::tooManyAttempts($key, $limit)) {
            throw ValidationException::withMessages(['email' => __('larawebkit-security.too_many', ['seconds' => RateLimiter::availableIn($key)])]);
        }
        RateLimiter::hit($key, 60);

        return $next($request);
    }

    /** Pretend success so bots learn nothing. */
    private function reject(): Response
    {
        return back()->with('status', __('larawebkit-security.form_received'));
    }

    public static function timestamp(): string
    {
        return Crypt::encryptString((string) time());
    }
}

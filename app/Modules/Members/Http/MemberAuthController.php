<?php

namespace App\Modules\Members\Http;

use App\Modules\Members\Members;
use App\Modules\Members\Models\Member;
use App\Modules\Seo\SeoData;
use App\Modules\Themes\ThemeManager;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/** Register, log in/out, verify the e-mail address and reset the password (guard "member"). */
class MemberAuthController
{
    public function __construct(private ThemeManager $theme) {}

    public function showLogin(): Response
    {
        return $this->page('login', __('larawebkit-members.login.title'));
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = 'member-login:'.Str::lower($data['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            event(new Lockout($request));
            throw ValidationException::withMessages(['email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)])]);
        }

        if (! Auth::guard('member')->attempt(['email' => Str::lower($data['email']), 'password' => $data['password'], 'is_active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        Auth::guard('member')->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('members.account'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('member')->logout();
        $request->session()->regenerateToken();

        return redirect()->to(localized_path('/'));
    }

    public function showRegister(): Response
    {
        abort_unless(setting('members.registration', true), 404);

        return $this->page('register', __('larawebkit-members.register.title'));
    }

    public function register(Request $request): RedirectResponse
    {
        abort_unless(setting('members.registration', true), 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:members,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)],
            'terms' => ['accepted'],
        ]);

        $member = Member::create(['name' => $data['name'], 'email' => Str::lower($data['email']), 'password' => $data['password'], 'locale' => app()->getLocale()]);
        event(new Registered($member));
        Auth::guard('member')->login($member);
        $request->session()->regenerate();

        return redirect()->route('members.verification.notice');
    }

    public function verifyNotice(): Response|RedirectResponse
    {
        $member = Members::current();
        if ($member?->hasVerifiedEmail()) {
            return redirect()->route('members.account');
        }

        return $this->page('verify', __('larawebkit-members.verify.title'));
    }

    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $member = Member::findOrFail($id);
        abort_unless(hash_equals(sha1($member->getEmailForVerification()), $hash), 403);
        if (! $member->hasVerifiedEmail()) {
            $member->markEmailAsVerified();
            event(new Verified($member));
        }
        Auth::guard('member')->login($member);

        return redirect()->route('members.account')->with('status', __('larawebkit-members.verify.done'));
    }

    public function resendVerification(Request $request): RedirectResponse
    {
        $member = Members::current();
        if ($member && ! $member->hasVerifiedEmail()) {
            $member->sendEmailVerificationNotification();
        }

        return back()->with('status', __('larawebkit-members.verify.sent'));
    }

    public function showForgot(): Response
    {
        return $this->page('forgot', __('larawebkit-members.forgot.title'));
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::broker('members')->sendResetLink(['email' => Str::lower((string) $request->input('email'))]);

        // Same answer whether or not the address exists (no account enumeration).
        return back()->with('status', __('larawebkit-members.forgot.sent'));
    }

    public function showReset(Request $request, string $token): Response
    {
        return $this->page('reset', __('larawebkit-members.reset.title'), ['token' => $token, 'email' => is_string($email = $request->query('email')) ? $email : '']);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', PasswordRule::min(10)]]);
        $status = Password::broker('members')->reset(
            ['email' => Str::lower($data['email']), 'password' => $data['password'], 'password_confirmation' => $request->input('password_confirmation'), 'token' => $data['token']],
            function (Member $member, string $password) {
                $member->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($member));
            },
        );
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return redirect()->route('members.login')->with('status', __('larawebkit-members.reset.done'));
    }

    /** @param  array<string, mixed>  $data */
    private function page(string $view, string $title, array $data = []): Response
    {
        $seo = SeoData::make($title, '/'.request()->path())->noindex();

        return response()->view($this->theme->view('account.'.$view), $data + ['seo' => $seo, 'title' => $title])
            ->header('Cache-Control', 'no-store, private');
    }
}

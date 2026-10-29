<?php

namespace App\Modules\Members\Http;

use App\Modules\Members\Events\MemberDeleting;
use App\Modules\Members\Members;
use App\Modules\Seo\SeoData;
use App\Modules\Themes\ThemeManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/** The member's own account: profile, password and deleting the account (GDPR / KVKK). */
class AccountController
{
    public function show(ThemeManager $theme): Response
    {
        $member = Members::current();
        $seo = SeoData::make(__('larawebkit-members.account.title'), '/account')->noindex();

        return response()->view($theme->view('account.profile'), ['seo' => $seo, 'member' => $member, 'title' => __('larawebkit-members.account.title')])
            ->header('Cache-Control', 'no-store, private');
    }

    public function update(Request $request): RedirectResponse
    {
        $member = Members::current();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('members', 'email')->ignore($member->id)],
        ]);
        $email = Str::lower($data['email']);
        $changed = $email !== $member->email;
        $member->fill(['name' => $data['name'], 'email' => $email]);
        if ($changed) {
            $member->email_verified_at = null;
        }
        $member->save();
        if ($changed) {
            $member->sendEmailVerificationNotification();
        }

        return back()->with('status', __($changed ? 'larawebkit-members.account.email_changed' : 'larawebkit-members.account.saved'));
    }

    public function password(Request $request): RedirectResponse
    {
        $member = Members::current();
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)],
        ]);
        if (! Hash::check($data['current_password'], $member->password)) {
            throw ValidationException::withMessages(['current_password' => __('validation.current_password')]);
        }
        $member->forceFill(['password' => $data['password']])->save();
        Auth::guard('member')->logoutOtherDevices($data['password']);

        return back()->with('status', __('larawebkit-members.account.password_changed'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $member = Members::current();
        $request->validate(['password' => ['required', 'string']]);
        if (! Hash::check((string) $request->input('password'), $member->password)) {
            throw ValidationException::withMessages(['password' => __('validation.current_password')]);
        }

        event(new MemberDeleting($member));
        Auth::guard('member')->logout();
        $member->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(localized_path('/'))->with('status', __('larawebkit-members.account.deleted'));
    }
}

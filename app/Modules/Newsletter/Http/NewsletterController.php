<?php

namespace App\Modules\Newsletter\Http;

use App\Http\Controllers\Controller;
use App\Modules\Newsletter\Mail\ConfirmSubscription;
use App\Modules\Newsletter\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/** Double opt-in: subscribe → e-mail with confirm link → confirmed. Tokens are stored hashed. */
class NewsletterController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
    {
        abort_unless(setting('features.newsletter', false), 404);
        $data = $request->validate(['email' => ['required', 'email', 'max:190']]);

        $subscriber = Subscriber::firstOrNew(['email' => mb_strtolower($data['email'])]);
        // Same answer whether or not the address exists (no enumeration).
        if (! $subscriber->exists || ! $subscriber->confirmed_at || $subscriber->unsubscribed_at) {
            $token = Str::random(48);
            $subscriber->fill(['token' => hash('sha256', $token), 'locale' => app()->getLocale(), 'unsubscribed_at' => null, 'confirmed_at' => null])->save();
            Mail::to($subscriber->email)->send(new ConfirmSubscription($subscriber, $token));
        }

        return back()->with('status', __('larawebkit-front.newsletter.check_inbox'));
    }

    public function confirm(string $token): RedirectResponse
    {
        $subscriber = Subscriber::where('token', hash('sha256', $token))->whereNull('confirmed_at')->first();
        if (! $subscriber || $subscriber->updated_at->lt(now()->subDays(7))) {
            return redirect(localized_path('/'))->with('status', __('larawebkit-front.newsletter.invalid'));
        }
        $subscriber->update(['confirmed_at' => now()]);

        return redirect(localized_path('/'))->with('status', __('larawebkit-front.newsletter.confirmed'));
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $subscriber = Subscriber::where('token', hash('sha256', $token))->first();
        $subscriber?->update(['unsubscribed_at' => now()]);

        return redirect(localized_path('/'))->with('status', __($subscriber ? 'larawebkit-front.newsletter.unsubscribed' : 'larawebkit-front.newsletter.invalid'));
    }
}

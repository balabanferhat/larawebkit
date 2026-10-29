<?php

namespace App\Modules\Newsletter\Http;

use App\Http\Controllers\Controller;
use App\Modules\Core\Downloads\SignedDownload;
use App\Modules\Newsletter\Mail\ConfirmSubscription;
use App\Modules\Newsletter\Mail\Welcome;
use App\Modules\Newsletter\Models\CampaignSend;
use App\Modules\Newsletter\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/** Double opt-in: subscribe → e-mail with confirm link → confirmed. Tokens are stored hashed. */
class NewsletterController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
    {
        abort_unless(setting('features.newsletter', false), 404);
        $consent = (bool) setting('newsletter.require_consent', false);
        $data = $request->validate(['email' => ['required', 'email', 'max:190'], 'consent' => $consent ? ['accepted'] : ['nullable']]);

        $subscriber = Subscriber::firstOrNew(['email' => mb_strtolower($data['email'])]);
        // Same answer whether or not the address exists (no enumeration).
        if (! $subscriber->exists || ! $subscriber->confirmed_at || $subscriber->unsubscribed_at) {
            $token = Str::random(48);
            $subscriber->fill(['token' => hash('sha256', $token), 'locale' => app()->getLocale(), 'unsubscribed_at' => null, 'confirmed_at' => null]);
            if ($consent) {
                $subscriber->fill(['consent_version' => (int) setting('newsletter.consent_version', 1), 'consent_text' => Subscriber::consentText(), 'consented_at' => now()]);
            }
            $subscriber->save();
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

        // Lead magnet: a signed, expiring download link, on the page and in a welcome e-mail.
        $download = null;
        if ($path = setting('newsletter.download')) {
            $download = SignedDownload::url($path, (string) (setting('newsletter.download_name') ?: basename($path)));
            Mail::to($subscriber->email)->locale($subscriber->locale)->queue(new Welcome($download));
        }

        return redirect(localized_path('/'))->with('status', __('larawebkit-front.newsletter.confirmed'))->with('download', $download);
    }

    public function unsubscribeSigned(Request $request, Subscriber $subscriber): RedirectResponse|Response
    {
        if (! $subscriber->unsubscribed_at) {
            // Attributed to the last campaign the person received (campaign stats: unsubscribes).
            $subscriber->update(['unsubscribed_at' => now(), 'unsubscribed_campaign_id' => $this->lastCampaignId($subscriber)]);
        }
        // Mail clients doing one-click unsubscribe only need a 2xx.
        if ($request->isMethod('post')) {
            return response('', 200);
        }

        return redirect(localized_path('/'))->with('status', __('larawebkit-front.newsletter.unsubscribed'));
    }

    private function lastCampaignId(Subscriber $subscriber): ?int
    {
        return CampaignSend::where('subscriber_id', $subscriber->id)->where('status', 'sent')->latest('sent_at')->value('campaign_id');
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $subscriber = Subscriber::where('token', hash('sha256', $token))->first();
        $subscriber?->update(['unsubscribed_at' => now()]);

        return redirect(localized_path('/'))->with('status', __($subscriber ? 'larawebkit-front.newsletter.unsubscribed' : 'larawebkit-front.newsletter.invalid'));
    }
}

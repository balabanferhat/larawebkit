<?php

namespace App\Modules\Events\Http;

use App\Modules\Events\Ics;
use App\Modules\Events\Mail\RegistrationConfirmation;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\Registration;
use App\Modules\Members\Members;
use App\Modules\Seo\SeoData;
use App\Modules\Themes\ThemeManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EventController
{
    public function __construct(private ThemeManager $theme) {}

    public function index(): Response
    {
        $locale = app()->getLocale();
        $upcoming = Event::listed()->upcoming()->where('locale', $locale)->with('featuredMedia')->orderBy('starts_at')
            ->paginate((int) config('larawebkit-events.per_page', 12));
        $past = Event::listed()->where('locale', $locale)->where('starts_at', '<', now())->whereNotIn('id', $upcoming->pluck('id'))
            ->latest('starts_at')->limit(6)->get();
        $seo = SeoData::forListing(__('larawebkit-events.plural'), localized_path('/events'), $upcoming);
        $seo->schema[] = ['@type' => 'ItemList', 'itemListElement' => $upcoming->values()->map(fn (Event $e, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => url($e->publicPath())])->all()];

        return response()->view($this->theme->view('events.index'), ['upcoming' => $upcoming, 'past' => $past, 'seo' => $seo]);
    }

    public function show(string $slug): Response
    {
        $event = Event::listed()->where('locale', app()->getLocale())->where('slug', $slug)->with('featuredMedia')->firstOrFail();
        $seo = SeoData::make($event->title, $event->publicPath(), $event->summary ?: Str::limit(strip_tags((string) $event->description), 160));
        $seo->image = $event->featuredMedia;
        $seo->breadcrumbs[] = ['name' => __('larawebkit-events.plural'), 'url' => url(localized_path('/events'))];
        $seo->breadcrumbs[] = ['name' => $event->title, 'url' => $seo->canonical];
        $seo->schema[] = $event->schema();

        return response()->view($this->theme->view('events.show'), ['event' => $event, 'seo' => $seo, 'member' => Members::current()]);
    }

    public function register(Request $request, string $event): RedirectResponse
    {
        $event = Event::published()->where('slug', $event)->when($request->input('locale'), fn ($q, $l) => $q->where('locale', $l))->firstOrFail();
        $member = Members::current();
        $data = $request->validate([
            'name' => [$member ? 'nullable' : 'required', 'string', 'max:120'],
            'email' => [$member ? 'nullable' : 'required', 'email:rfc', 'max:190'],
            'consent' => ['accepted'],
        ]);
        $name = $member ? $member->name : trim($data['name']);
        $email = Str::lower($member ? $member->email : trim($data['email']));

        $registration = DB::transaction(function () use ($event, $member, $name, $email) {
            $existing = Registration::where('event_id', $event->id)->where('email', $email)->lockForUpdate()->first();
            if ($existing?->status === 'registered') {
                return $existing; // already registered: the confirmation is sent again, nothing is revealed
            }
            if (! $event->fresh()->canRegister()) {
                throw ValidationException::withMessages(['email' => __($event->isFull() ? 'larawebkit-events.full' : 'larawebkit-events.closed')]);
            }
            $registration = $existing ?? new Registration(['event_id' => $event->id, 'email' => $email]);
            $registration->fill(['name' => $name, 'member_id' => $member?->id, 'status' => 'registered'])->save();

            return $registration;
        });

        Mail::to($email, $name)->queue(new RegistrationConfirmation($registration));

        // A verified member registering with their own address may see the link right away; guests
        // get it by e-mail only, so nobody can register with someone else's address to see it.
        if ($member && $member->hasVerifiedEmail()) {
            return redirect()->to($registration->url())->with('status', __('larawebkit-events.registration.confirmed'));
        }

        return redirect()->to(url()->previous().'#register')->with('event_status', __('larawebkit-events.register.sent'));
    }

    public function registration(string $event, Registration $registration): Response
    {
        $event = $this->own($event, $registration);
        app()->setLocale($event->locale);
        $seo = SeoData::make(__('larawebkit-events.registration.title').': '.$event->title, $event->publicPath())->noindex();

        return response()->view($this->theme->view('events.registration'), ['event' => $event, 'registration' => $registration, 'seo' => $seo])
            ->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer');
    }

    public function ics(string $event, Registration $registration): Response
    {
        $event = $this->own($event, $registration);
        abort_unless($registration->status === 'registered', 404);

        return response(Ics::make($event, $registration), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$event->slug.'.ics"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function cancel(string $event, Registration $registration): RedirectResponse
    {
        $this->own($event, $registration);
        $registration->update(['status' => 'cancelled']);

        return redirect()->to($registration->url())->with('status', __('larawebkit-events.registration.cancelled'));
    }

    private function own(string $slug, Registration $registration): Event
    {
        $event = $registration->event;
        abort_unless($event && $event->slug === $slug, 404);

        return $event;
    }
}

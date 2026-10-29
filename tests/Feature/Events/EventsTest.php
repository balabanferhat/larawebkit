<?php

use App\Modules\Events\Filament\Resources\Events\Pages\CreateEvent;
use App\Modules\Events\Filament\Resources\Events\Pages\EditEvent;
use App\Modules\Events\Filament\Resources\Events\RelationManagers\RegistrationsRelationManager;
use App\Modules\Events\Ics;
use App\Modules\Events\Mail\RegistrationConfirmation;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\Registration;
use App\Modules\Events\RegistrationExport;
use App\Modules\Members\Events\MemberDeleting;
use App\Modules\Members\Models\Member;
use App\Modules\Settings\SettingsRepository;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    app(SettingsRepository::class)->setMany(['features.events' => true, 'features.members' => true, 'features.page_cache' => false]);
    $this->withoutMiddleware(PreventRequestForgery::class);
});

function webinar(array $attributes = []): Event
{
    return Event::create($attributes + [
        'title' => 'Laravel webinar', 'slug' => 'laravel-webinar', 'status' => 'published', 'summary' => 'A short online session.',
        'description' => '<p>We build a site together.</p><script>alert(1)</script>',
        'starts_at' => now()->addWeek()->setTime(15, 0), 'timezone' => 'Europe/Istanbul', 'location_type' => 'online',
        'online_url' => 'https://meet.example.com/secret-room', 'capacity' => 2,
    ]);
}

function registerFor(Event $event, array $data = [])
{
    return test()->from($event->publicPath())->post(route('events.register', $event->slug), $data + [
        '_lwk_ts' => Crypt::encryptString((string) (time() - 10)), 'name' => 'Ann', 'email' => 'Ann@Example.com', 'consent' => '1',
    ]);
}

it('is hidden while the feature is off', function () {
    webinar();
    app(SettingsRepository::class)->set('features.events', false);

    $this->get('/events')->assertNotFound();
    $this->get('/events/laravel-webinar')->assertNotFound();
});

it('lists upcoming events and shows the Event schema without the private link', function () {
    $event = webinar();
    Event::create(['title' => 'Old meetup', 'status' => 'published', 'starts_at' => now()->subMonth(), 'location_type' => 'venue', 'venue_name' => 'Hall A']);
    Event::create(['title' => 'Secret draft', 'starts_at' => now()->addDay()]);

    $this->get('/events')->assertOk()->assertSee('Laravel webinar')->assertSee('Old meetup')->assertDontSee('Secret draft');

    $html = $this->get('/events/laravel-webinar')->assertOk()->assertSee('A short online session.')
        ->assertSee('name="consent"', false)->assertSee(trans_choice('larawebkit-events.spots_left', 2, ['count' => 2]))->getContent();
    expect($html)->not->toContain('secret-room')->not->toContain('<script>alert')
        ->toContain('"@type":"Event"')->toContain('OnlineEventAttendanceMode')->toContain('"startDate":"'.$event->localStart()->toIso8601String().'"');
    $this->get('/sitemap-events.xml')->assertOk()->assertSee(url('/events/laravel-webinar'));
});

it('registers a guest, e-mails the calendar file and shows the link only on the signed page', function () {
    Mail::fake();
    $event = webinar();

    registerFor($event)->assertRedirect()->assertSessionHas('event_status');

    $registration = Registration::sole();
    expect($registration->email)->toBe('ann@example.com')->and($registration->member_id)->toBeNull();
    Mail::assertQueued(RegistrationConfirmation::class, function (RegistrationConfirmation $mail) {
        $attachment = $mail->attachments()[0];

        return $mail->hasTo('ann@example.com') && $attachment->as === 'laravel-webinar.ics'
            && str_contains($mail->render(), 'secret-room');
    });

    $this->get($registration->url())->assertOk()->assertSee('https://meet.example.com/secret-room')->assertHeader('Cache-Control', 'no-store, private');
    $this->get(route('events.registration', ['event' => 'laravel-webinar', 'registration' => $registration->id]))->assertForbidden();
    $ics = $this->get($registration->icsUrl())->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8')->getContent();
    expect($ics)->toContain('BEGIN:VEVENT')->toContain('SUMMARY:Laravel webinar')->toContain('secret-room')
        ->and(Ics::make($event))->not->toContain('secret-room');
});

it('enforces the capacity and does not register an address twice', function () {
    Mail::fake();
    $event = webinar(['capacity' => 1]);

    registerFor($event);
    registerFor($event, ['email' => 'ann@example.com'])->assertSessionHas('event_status');
    expect(Registration::count())->toBe(1);
    Mail::assertQueued(RegistrationConfirmation::class, 2);

    registerFor($event, ['email' => 'bob@example.com'])->assertSessionHasErrors('email');
    expect(Registration::count())->toBe(1);
    $this->get('/events/laravel-webinar')->assertSee(__('larawebkit-events.full'))->assertDontSee('name="consent"', false);

    // Cancelling frees the spot.
    $registration = Registration::sole();
    $this->post(URL::signedRoute('events.registration.cancel', ['event' => 'laravel-webinar', 'registration' => $registration->id]))->assertRedirect($registration->url());
    expect($registration->fresh()->status)->toBe('cancelled');
    $this->get($registration->icsUrl())->assertNotFound();
    registerFor($event, ['email' => 'bob@example.com'])->assertSessionHasNoErrors();
});

it('requires consent and refuses closed, past and draft events', function () {
    $event = webinar();
    registerFor($event, ['consent' => null])->assertSessionHasErrors('consent');

    $event->update(['registration_open' => false]);
    registerFor($event)->assertSessionHasErrors('email');

    $past = webinar(['slug' => 'past', 'starts_at' => now()->subDays(2)]);
    registerFor($past)->assertSessionHasErrors('email');

    $draft = webinar(['slug' => 'draft', 'status' => 'draft']);
    registerFor($draft)->assertNotFound();
    expect(Registration::count())->toBe(0);
});

it('registers a verified member in one click and shows their page right away', function () {
    Mail::fake();
    $event = webinar();
    $member = Member::create(['name' => 'Reader', 'email' => 'reader@example.com', 'password' => 'correct-horse-battery']);
    $member->forceFill(['email_verified_at' => now()])->save();
    $this->actingAs($member, 'member');

    $this->get('/events/laravel-webinar')->assertSee('reader@example.com')->assertDontSee('name="email"', false);
    $registration = null;
    registerFor($event, ['name' => null, 'email' => null])->assertRedirect();
    $registration = Registration::sole();
    expect($registration->member_id)->toBe($member->id)->and($registration->email)->toBe('reader@example.com');

    // Deleting the account removes the registration (GDPR / KVKK).
    event(new MemberDeleting($member));
    expect(Registration::count())->toBe(0);
});

it('creates events in the event time zone and sends the link to registrants', function () {
    Mail::fake();
    $this->actingAs(superAdmin(), 'web');

    Livewire::test(CreateEvent::class)
        ->fillForm(['title' => 'Workshop', 'status' => 'published', 'timezone' => 'Europe/Istanbul', 'starts_at' => '2030-05-01 10:00', 'location_type' => 'online', 'online_url' => 'https://meet.example.com/w'])
        ->call('create')->assertHasNoFormErrors();
    $event = Event::where('slug', 'workshop')->sole();
    expect($event->starts_at->utc()->format('Y-m-d H:i'))->toBe('2030-05-01 07:00');

    $event->registrations()->createMany([['name' => 'A', 'email' => 'a@example.com'], ['name' => 'B', 'email' => 'b@example.com', 'status' => 'cancelled']]);
    Livewire::test(EditEvent::class, ['record' => $event->id])->callAction('sendLink');
    Mail::assertQueued(RegistrationConfirmation::class, fn ($m) => $m->link && $m->hasTo('a@example.com'));
    Mail::assertNotQueued(RegistrationConfirmation::class, fn ($m) => $m->hasTo('b@example.com'));
    expect($event->registrations()->where('email', 'a@example.com')->first()->link_sent_at)->not->toBeNull();

    Livewire::test(RegistrationsRelationManager::class, ['ownerRecord' => $event, 'pageClass' => EditEvent::class])->assertSee('a@example.com');
});

it('exports registrations as CSV', function () {
    $event = webinar();
    $event->registrations()->create(['name' => '=cmd()', 'email' => 'a@example.com']);

    ob_start();
    RegistrationExport::csv($event)->sendContent();
    $csv = (string) ob_get_clean();

    expect($csv)->toStartWith("\xEF\xBB\xBF")->toContain("'=cmd()")->toContain('a@example.com');
});

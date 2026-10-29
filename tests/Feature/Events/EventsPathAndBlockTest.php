<?php

use App\Modules\Blocks\BlockRegistry;
use App\Modules\Content\Models\Page;
use App\Modules\Events\Models\Event;
use App\Modules\Settings\SettingsRepository;

afterEach(function () {
    putenv('EVENTS_PATHS');
    unset($_ENV['EVENTS_PATHS'], $_SERVER['EVENTS_PATHS']);
});

function upcoming(array $attributes = []): Event
{
    return Event::create($attributes + ['title' => 'Spring meetup', 'slug' => 'spring-meetup', 'status' => 'published', 'starts_at' => now()->addWeek(), 'location_type' => 'venue']);
}

it('serves the events pages at a path per language', function () {
    putenv('EVENTS_PATHS=tr:etkinlikler');
    $_ENV['EVENTS_PATHS'] = $_SERVER['EVENTS_PATHS'] = 'tr:etkinlikler';
    $this->refreshApplication();
    $this->artisan('migrate');
    app(SettingsRepository::class)->setMany(['features.events' => true, 'features.page_cache' => false, 'general.default_locale' => 'tr', 'general.active_locales' => ['tr', 'en']]);

    $tr = upcoming(['locale' => 'tr', 'title' => 'Bahar buluşması', 'slug' => 'bahar-bulusmasi']);
    $en = upcoming(['locale' => 'en']);

    expect($tr->publicPath())->toBe('/etkinlikler/bahar-bulusmasi')->and($en->publicPath())->toBe('/en/events/spring-meetup');
    $this->get('/etkinlikler')->assertOk()->assertSee('Bahar buluşması')->assertDontSee('Spring meetup');
    $this->get('/etkinlikler/bahar-bulusmasi')->assertOk()->assertSee('rel="canonical" href="'.url('/etkinlikler/bahar-bulusmasi').'"', false);
    $this->get('/en/events/spring-meetup')->assertOk();
});

it('shows upcoming events in a page block, and nothing while events are off', function () {
    app(SettingsRepository::class)->setMany(['features.events' => true, 'features.page_cache' => false]);
    upcoming();
    Event::create(['title' => 'Old one', 'status' => 'published', 'starts_at' => now()->subMonth()]);
    Page::factory()->published()->create(['slug' => 'home-blocks', 'blocks' => [
        ['type' => 'upcoming_events', 'data' => ['heading' => 'Come along', 'count' => 3, 'variant' => 'list']],
    ]]);

    expect(app(BlockRegistry::class)->get('upcoming_events'))->not->toBeNull();
    $this->get('/home-blocks')->assertOk()->assertSee('Come along')->assertSee('Spring meetup')->assertDontSee('Old one')->assertSee('href="/events"', false);

    app(SettingsRepository::class)->set('features.events', false);
    $this->get('/home-blocks')->assertOk()->assertDontSee('Come along')->assertDontSee('Spring meetup');
});

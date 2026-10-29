<?php

namespace App\Modules\Events;

use App\Modules\Core\ModuleRegistry;
use App\Modules\Events\Blocks\UpcomingEvents;
use App\Modules\Events\Http\EnsureEventsEnabled;
use App\Modules\Events\Http\EventController;
use App\Modules\Events\Listeners\ForgetMemberRegistrations;
use App\Modules\Events\Models\Event;
use App\Modules\Members\Events\MemberDeleting;
use App\Modules\Seo\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Event as EventBus;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Optional module (Settings → Features → Events): events with free registration for members
 * and guests, a calendar file, and an online link that only registrants see.
 */
class EventsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The "Upcoming events" section block, when the blocks module is there.
        if (ModuleRegistry::enabled('blocks')) {
            config(['larawebkit-blocks.types' => [...(array) config('larawebkit-blocks.types', []), UpcomingEvents::class]]);
        }
    }

    public function boot(): void
    {
        $this->app['router']->aliasMiddleware('events.enabled', EnsureEventsEnabled::class);
        $locales = implode('|', array_map(fn ($l) => preg_quote($l, '/'), array_keys((array) config('larawebkit.locales'))));
        // Every configured path is routed (the main one named); each event's canonical URL uses its language's path.
        $front = function () {
            foreach (Events::paths() as $i => $path) {
                $index = Route::get('/'.$path, [EventController::class, 'index']);
                $show = Route::get('/'.$path.'/{slug}', [EventController::class, 'show'])->where('slug', '[a-z0-9-]+');
                if ($i === 0) {
                    $index->name('index');
                    $show->name('show');
                }
            }
        };

        Route::middleware(['web', 'events.enabled', 'front-locale'])->group(function () use ($front, $locales) {
            Route::name('events.')->group($front);
            Route::prefix('{locale}')->where(['locale' => $locales])->name('events.localized.')->group($front);
        });

        // Registration and the registrant's own (signed) page; not localised, the event carries its locale.
        Route::middleware(['web', 'events.enabled'])->prefix(Events::paths()[0].'/{event}')->where(['event' => '[a-z0-9-]+'])->name('events.')->group(function () {
            Route::post('/register', [EventController::class, 'register'])->middleware('public-form')->name('register');
            Route::middleware('signed')->group(function () {
                Route::get('/r/{registration}', [EventController::class, 'registration'])->whereNumber('registration')->name('registration');
                Route::get('/r/{registration}/calendar.ics', [EventController::class, 'ics'])->whereNumber('registration')->name('registration.ics');
                Route::post('/r/{registration}/cancel', [EventController::class, 'cancel'])->whereNumber('registration')->name('registration.cancel');
            });
        });

        if (ModuleRegistry::enabled('seo')) {
            SitemapController::extend('events', fn () => Event::listed()->when(! Events::enabled(), fn ($q) => $q->whereRaw('1 = 0'))->with('featuredMedia')->latest('starts_at'));
        }

        if (ModuleRegistry::enabled('members')) {
            EventBus::listen(MemberDeleting::class, ForgetMemberRegistrations::class);
        }
    }
}

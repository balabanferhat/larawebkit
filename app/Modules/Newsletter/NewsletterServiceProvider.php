<?php

namespace App\Modules\Newsletter;

use App\Modules\Newsletter\Http\NewsletterController;
use App\Modules\Newsletter\Jobs\SendNewsletterBatch;
use App\Modules\Newsletter\Models\Campaign;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Throwable;

/** Optional module: enable "Newsletter" in Settings → Features. */
class NewsletterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->prefix('newsletter')->name('newsletter.')->group(function () {
            Route::post('/subscribe', [NewsletterController::class, 'subscribe'])->middleware('public-form')->name('subscribe');
            Route::get('/confirm/{token}', [NewsletterController::class, 'confirm'])->where('token', '[A-Za-z0-9]{48}')->name('confirm');
            Route::get('/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->where('token', '[A-Za-z0-9]{48}')->name('unsubscribe');
            // Campaign e-mails: signed per-subscriber link; POST is the RFC 8058 one-click unsubscribe (no CSRF token possible).
            Route::match(['get', 'post'], '/leave/{subscriber}', [NewsletterController::class, 'unsubscribeSigned'])
                ->whereNumber('subscriber')->middleware('signed')->withoutMiddleware(PreventRequestForgery::class)->name('unsubscribe.signed');
        });

        // One batch per minute while a campaign is sending (or due); drained by the queue cron.
        $this->app->booted(fn () => Schedule::call(function () {
            try {
                if (Campaign::whereIn('status', ['sending'])->exists() || Campaign::where('status', 'scheduled')->where('scheduled_at', '<=', now())->exists()) {
                    SendNewsletterBatch::dispatch();
                }
            } catch (Throwable) {
                // No tables yet (fresh install).
            }
        })->everyMinute()->name('larawebkit:newsletter:batch'));
    }
}

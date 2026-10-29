<?php

namespace App\Modules\Newsletter;

use App\Modules\Newsletter\Http\NewsletterController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/** Optional module: enable "Newsletter" in Settings → Features. */
class NewsletterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware('web')->prefix('newsletter')->name('newsletter.')->group(function () {
            Route::post('/subscribe', [NewsletterController::class, 'subscribe'])->middleware('public-form')->name('subscribe');
            Route::get('/confirm/{token}', [NewsletterController::class, 'confirm'])->where('token', '[A-Za-z0-9]{48}')->name('confirm');
            Route::get('/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->where('token', '[A-Za-z0-9]{48}')->name('unsubscribe');
        });
    }
}

<?php

namespace App\Modules\Geo;

use App\Modules\Content\Events\ContentSaved;
use App\Modules\Settings\Models\Setting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class GeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiCrawlerPolicy::class);
        $this->app->singleton(LlmsTxt::class);
    }

    public function boot(): void
    {
        Event::listen(ContentSaved::class, fn () => LlmsTxt::flush());
        Setting::saved(fn () => LlmsTxt::flush());

        Route::middleware('web')->group(function () {
            Route::get('/llms.txt', fn () => response(app(LlmsTxt::class)->index(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']))->name('larawebkit.llms');
            Route::get('/llms-full.txt', fn () => response(app(LlmsTxt::class)->full(), 200, ['Content-Type' => 'text/plain; charset=UTF-8']))->name('larawebkit.llms-full');
        });
    }
}

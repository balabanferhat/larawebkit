<?php

namespace App\Modules\Editor;

use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Editor\Http\Controllers\PexelsController;
use App\Modules\Editor\Pexels\PexelsClient;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class EditorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ContentScorer::class);
        $this->app->singleton(PublishGate::class);
        $this->app->singleton(PexelsClient::class);
    }

    public function boot(): void
    {
        // Authoritative scores are stored on every save.
        foreach ([Post::class, Page::class] as $model) {
            if (class_exists($model)) {
                $model::saving(fn ($content) => app(ContentScorer::class)->apply($content));
            }
        }

        // Plain JS/CSS, no build step: published to public/ by `php artisan filament:assets`.
        FilamentAsset::register([
            Js::make('larawebkit-scoring', resource_path('js/larawebkit/scoring.js')),
            Js::make('larawebkit-editor', resource_path('js/larawebkit/editor.js')),
            Css::make('larawebkit-admin', resource_path('css/larawebkit-admin.css')),
        ], 'larawebkit');

        Route::middleware(['web', 'auth', 'throttle:60,1'])
            ->prefix(trim((string) config('larawebkit.admin_path', 'admin'), '/').'/larawebkit/pexels')
            ->name('larawebkit.pexels.')
            ->group(function () {
                Route::get('/search', [PexelsController::class, 'search'])->name('search');
                Route::post('/import', [PexelsController::class, 'import'])->name('import');
                Route::post('/suggest', [PexelsController::class, 'suggest'])->name('suggest');
            });
    }
}

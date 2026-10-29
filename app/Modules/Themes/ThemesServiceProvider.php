<?php

namespace App\Modules\Themes;

use App\Modules\Themes\Console\BuildThemesCommand;
use App\Modules\Themes\Console\DemoCommand;
use App\Modules\Themes\Console\PublishThemesCommand;
use App\Modules\Themes\Design\DesignTokens;
use App\Modules\Themes\Design\Favicons;
use App\Modules\Themes\Http\ThemeAssetController;
use App\Modules\Themes\Http\WebManifestController;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ThemesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeManager::class);
        $this->app->singleton(DesignTokens::class);
        $this->app->singleton(Favicons::class);
    }

    public function boot(): void
    {
        // Branded e-mails and notifications use the site name from the settings.
        if (! $this->app->runningUnitTests()) {
            config(['app.name' => (string) setting('general.site_name', config('app.name'))]);
        }

        // @themeStyles: the active theme's prebuilt CSS/JS (published copy or PHP fallback route).
        Blade::directive('themeStyles', fn () => '<?php echo app(\App\Modules\Themes\ThemeAssets::class)->styles(); ?>');

        if ($this->app->runningInConsole()) {
            $this->commands([BuildThemesCommand::class, PublishThemesCommand::class, DemoCommand::class]);
        }

        Route::get('/site.webmanifest', WebManifestController::class)->name('larawebkit.manifest');
        // No session/cookies for static assets.
        Route::get('/theme-assets/{theme}/{path}', ThemeAssetController::class)
            ->where(['theme' => '[a-z0-9-]+', 'path' => '[A-Za-z0-9/_.-]+'])
            ->name('larawebkit.theme-asset');
    }
}

<?php

namespace App\Modules\Themes;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ThemesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ThemeManager::class);
    }

    public function boot(): void
    {
        // Branded e-mails and notifications use the site name from the settings.
        if (! $this->app->runningUnitTests()) {
            config(['app.name' => (string) setting('general.site_name', config('app.name'))]);
        }

        // @themeAsset: the active theme's CSS through Vite, or a tiny inline fallback when
        // assets were not built (fresh clone, shared hosting without a build upload).
        Blade::directive('themeStyles', fn () => '<?php echo app(\App\Modules\Themes\ThemeAssets::class)->styles(); ?>');
    }
}

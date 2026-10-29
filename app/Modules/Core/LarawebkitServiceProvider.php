<?php

namespace App\Modules\Core;

use App\Models\User;
use App\Modules\Content\Models\Category;
use App\Modules\Content\Models\Menu;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Content\Models\Redirect;
use App\Modules\Content\Models\Tag;
use App\Modules\Core\Console\DeployCheckCommand;
use App\Modules\Core\Console\InstallCommand;
use App\Modules\Core\Downloads\DownloadController;
use App\Modules\Media\Models\Media;
use App\Modules\Settings\Models\Setting;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;

/**
 * Boots the module system. Every module listed in config/larawebkit.php
 * registers its own provider from here, so dropping a module is one line.
 */
class LarawebkitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach (ModuleRegistry::providers() as $provider) {
            $this->app->register($provider);
        }
    }

    /** Switch the app to the site's default language (console runs; requests get theirs from the URL). */
    public static function useSiteLocale(): void
    {
        $locale = rescue(fn () => (string) setting('general.default_locale', ''), '', report: false);
        if ($locale !== '' && array_key_exists($locale, (array) config('larawebkit.locales'))) {
            app()->setLocale($locale);
        }
    }

    public function boot(): void
    {
        // Console commands build URLs (redirects, sitemaps, e-mails) in the site language, not config('app.locale'):
        // blog_path() is /blog on a Turkish site, not /en/blog. A command may still switch (import --locale).
        if ($this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            $this->app->booted(fn () => self::useSiteLocale());
        }

        // Short, stable type names in audit logs, media usages and revisions.
        Relation::morphMap([
            'user' => User::class,
            'setting' => Setting::class,
            'media' => Media::class,
            'page' => Page::class,
            'post' => Post::class,
            'category' => Category::class,
            'tag' => Tag::class,
            'redirect' => Redirect::class,
            'menu' => Menu::class,
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class, DeployCheckCommand::class]);
        }

        // Signed, expiring links to private files (SignedDownload::url()).
        Route::middleware(['web', 'signed', 'throttle:30,1'])->get('/download', DownloadController::class)->name('larawebkit.download');

        $this->app->booted(function () {
            Schedule::call(fn () => @file_put_contents((string) config('larawebkit.heartbeat_file'), (string) time()))
                ->name('larawebkit:heartbeat')
                ->everyMinute();

            // Shared hosting: no long-running worker, the scheduler drains the database queue.
            Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
                ->name('larawebkit:queue')
                ->everyMinute()
                ->withoutOverlapping();
        });
    }
}

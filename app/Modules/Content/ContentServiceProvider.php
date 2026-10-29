<?php

namespace App\Modules\Content;

use App\Modules\Content\Console\AnnounceScheduledCommand;
use App\Modules\Content\Http\Controllers\ContentController;
use App\Modules\Content\Http\Middleware\HandleRedirects;
use App\Modules\Content\Http\Middleware\SetFrontLocale;
use App\Modules\Content\Support\HtmlSanitizer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;

class ContentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HtmlSanitizer::class);
    }

    public function boot(): void
    {
        $router = $this->app['router'];
        $router->pushMiddlewareToGroup('web', HandleRedirects::class);
        $router->aliasMiddleware('front-locale', SetFrontLocale::class);

        if ($this->app->runningInConsole()) {
            $this->commands([AnnounceScheduledCommand::class]);
        }
        $this->app->booted(fn () => Schedule::command('larawebkit:content:announce')->everyMinute()->name('larawebkit:content:announce')->withoutOverlapping());

        // Registered after all other routes so the page catch-all comes last.
        $this->app->booted(fn () => $this->registerFrontRoutes());
    }

    public static function frontRoutes(): void
    {
        Route::get('/', [ContentController::class, 'home'])->name('home');
        $blog = '/'.config('larawebkit-content.blog_path', 'blog');
        $prefix = (string) config('larawebkit-content.post_prefix', 'blog');
        Route::get($blog, [ContentController::class, 'blog'])->name('blog');
        Route::get($blog.'/category/{slug}', [ContentController::class, 'category'])->name('category');
        Route::get($blog.'/tag/{slug}', [ContentController::class, 'tag'])->name('tag');
        // Without a prefix posts are served by the page catch-all (ContentController::page, pages first).
        if ($prefix !== '') {
            Route::get('/'.$prefix.'/{slug}', [ContentController::class, 'post'])->name('post');
        }
        Route::get('/author/{slug}', [ContentController::class, 'author'])->name('author');
        Route::get('/search', [ContentController::class, 'search'])->name('search');
    }

    private function registerFrontRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $admin = preg_quote(trim((string) config('larawebkit.admin_path', 'admin'), '/'), '/');
        $reserved = "(?!$admin(?:/|$)|livewire|filament|storage/|build/|up$|_preview)";
        $locales = implode('|', array_map(fn ($l) => preg_quote($l, '/'), array_keys((array) config('larawebkit.locales'))));

        Route::middleware(['web', 'front-locale'])->group(function () use ($reserved, $locales) {
            Route::middleware('auth')->get('/_preview/{type}/{id}', [ContentController::class, 'preview'])->name('larawebkit.preview');
            // Shareable draft preview (Share preview link): signed and expiring, no login.
            Route::middleware(['signed', 'throttle:60,1'])->get('/_preview/shared/{type}/{id}', [ContentController::class, 'sharedPreview'])
                ->whereIn('type', ['post', 'page'])->whereNumber('id')->name('larawebkit.preview.shared');

            Route::name('front.')->group(fn () => self::frontRoutes());
            Route::prefix('{locale}')->where(['locale' => $locales])->name('front.localized.')->group(function () use ($reserved) {
                self::frontRoutes();
                Route::get('/{path}', [ContentController::class, 'page'])->where('path', $reserved.'.+')->name('page');
            });
            Route::get('/{path}', [ContentController::class, 'page'])->where('path', $reserved.'.+')->name('front.page');
        });
    }
}

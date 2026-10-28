<?php

namespace App\Modules\Seo;

use App\Modules\Content\Models\Menu;
use App\Modules\Content\Models\MenuItem;
use App\Modules\Content\Models\Redirect;
use App\Modules\Seo\Http\Controllers\FeedController;
use App\Modules\Seo\Http\Controllers\RobotsController;
use App\Modules\Seo\Http\Controllers\SitemapController;
use App\Modules\Seo\Http\Middleware\CanonicalHost;
use App\Modules\Seo\Http\Middleware\PageCache;
use App\Modules\Seo\Listeners\RefreshSeoCaches;
use App\Modules\Seo\Models\NotFoundLog;
use App\Modules\Settings\Models\Setting;
use App\Modules\Themes\ThemeManager;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class SeoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $router = $this->app['router'];
        $router->prependMiddlewareToGroup('web', CanonicalHost::class);
        $router->pushMiddlewareToGroup('web', PageCache::class);
        Event::subscribe(RefreshSeoCaches::class);
        // Settings (theme, robots rules, site name…) and menus appear on every cached page.
        foreach ([Setting::class, Menu::class, MenuItem::class, Redirect::class] as $model) {
            $model::saved(fn () => PageCache::bust());
            $model::deleted(fn () => PageCache::bust());
        }

        Route::middleware('web')->group(function () {
            Route::get('/robots.txt', RobotsController::class)->name('larawebkit.robots');
            Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('larawebkit.sitemap');
            Route::get('/sitemap-{type}.xml', [SitemapController::class, 'show'])->where('type', '[a-z]+')->name('larawebkit.sitemap.type');
            Route::get('/feed.xml', FeedController::class)->name('larawebkit.feed');
            Route::get('/{key}.txt', function (string $key) {
                abort_unless(filled(config('services.indexnow.key')) && hash_equals((string) config('services.indexnow.key'), $key), 404);

                return response($key, 200, ['Content-Type' => 'text/plain']);
            })->where('key', '(?!llms)[A-Za-z0-9\-]{8,128}')->name('larawebkit.indexnow-key');
        });

        // Themed 404 / 410 pages for the public site, and the 404 log.
        $this->app->make(ExceptionHandler::class)->renderable(function (HttpExceptionInterface $e, Request $request) {
            $admin = trim((string) config('larawebkit.admin_path', 'admin'), '/');
            if (! in_array($e->getStatusCode(), [404, 410], true) || $request->expectsJson() || $request->is($admin, $admin.'/*', 'livewire*')) {
                return null;
            }
            if ($e->getStatusCode() === 404 && $request->isMethod('GET')) {
                NotFoundLog::hit($request);
            }
            try {
                $seo = SeoData::make(__('larawebkit-front.not_found_title'), '/'.$request->path())->noindex();

                return response()->view(app(ThemeManager::class)->view('errors.404'), ['seo' => $seo, 'status' => $e->getStatusCode()], $e->getStatusCode());
            } catch (\Throwable) {
                return null;
            }
        });
    }
}

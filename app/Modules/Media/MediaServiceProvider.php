<?php

namespace App\Modules\Media;

use App\Modules\Media\Console\RegenerateMediaCommand;
use App\Modules\Media\Http\Controllers\MediaApiController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ImagePipeline::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([RegenerateMediaCommand::class]);
        }

        Route::middleware(['web', 'auth'])
            ->prefix(trim((string) config('larawebkit.admin_path', 'admin'), '/').'/larawebkit/media')
            ->name('larawebkit.media.')
            ->group(function () {
                Route::get('/', [MediaApiController::class, 'index'])->name('index');
                Route::post('/', [MediaApiController::class, 'store'])->name('store');
                Route::get('/{media}', [MediaApiController::class, 'show'])->name('show');
                Route::patch('/{media}', [MediaApiController::class, 'update'])->name('update');
                Route::delete('/{media}', [MediaApiController::class, 'destroy'])->name('destroy');
            });
    }
}

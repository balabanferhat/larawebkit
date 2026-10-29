<?php

namespace App\Modules\Ai;

use App\Modules\Ai\Http\Controllers\AiController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AiManager::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'auth', 'throttle:30,1'])
            ->prefix(trim((string) config('larawebkit.admin_path', 'admin'), '/').'/larawebkit/ai')
            ->name('larawebkit.ai.')
            ->group(function () {
                Route::get('/tasks/{task}', [AiController::class, 'task'])->name('task');
                Route::post('/{action}', [AiController::class, 'run'])->where('action', '[a-z_]+')->name('run');
            });
    }
}

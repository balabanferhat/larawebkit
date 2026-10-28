<?php

namespace App\Modules\Core;

use App\Modules\Core\Console\DeployCheckCommand;
use App\Modules\Core\Console\InstallCommand;
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

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class, DeployCheckCommand::class]);
        }

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

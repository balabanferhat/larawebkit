<?php

namespace App\Modules\Logs;

use App\Modules\Logs\Console\PruneLogsCommand;
use App\Modules\Logs\Http\Middleware\LogAccess;
use App\Modules\Logs\Listeners\AuthEventSubscriber;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;

class LogsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LogAccess::class);
    }

    public function boot(): void
    {
        Event::subscribe(AuthEventSubscriber::class);

        $this->app['router']->pushMiddlewareToGroup('web', LogAccess::class);

        if ($this->app->runningInConsole()) {
            $this->commands([PruneLogsCommand::class]);
        }

        $this->app->booted(fn () => Schedule::command('larawebkit:logs:prune')->daily()->name('larawebkit:logs:prune'));
    }
}

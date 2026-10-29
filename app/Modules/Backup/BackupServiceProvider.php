<?php

namespace App\Modules\Backup;

use App\Modules\Backup\Console\BackupCommand;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Throwable;

/** Backups: `larawebkit:backup`, a schedule (Settings → Backups) and System → Backups in the admin. */
class BackupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BackupManager::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([BackupCommand::class]);
        }

        $this->app->booted(function () {
            try {
                $frequency = (string) setting('backup.frequency', 'daily');
                $time = preg_match('/^\d{2}:\d{2}$/', (string) setting('backup.time', '03:00')) ? (string) setting('backup.time', '03:00') : '03:00';
            } catch (Throwable) {
                return; // no settings table yet
            }
            $event = Schedule::command('larawebkit:backup')->name('larawebkit:backup')->withoutOverlapping(120);
            match ($frequency) {
                'weekly' => $event->weeklyOn(0, $time),
                'daily' => $event->dailyAt($time),
                default => $event->when(fn () => false),
            };
        });
    }
}

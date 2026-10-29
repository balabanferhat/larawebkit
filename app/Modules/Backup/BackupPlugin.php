<?php

namespace App\Modules\Backup;

use App\Modules\Backup\Filament\Pages\ManageBackups;
use Filament\Contracts\Plugin;
use Filament\Panel;

class BackupPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'larawebkit-backup';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([ManageBackups::class]);
    }

    public function boot(Panel $panel): void {}
}

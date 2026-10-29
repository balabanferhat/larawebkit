<?php

namespace App\Modules\Settings;

use App\Modules\Settings\Filament\Pages\ManageSettings;
use Filament\Contracts\Plugin;
use Filament\Panel;

class SettingsPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'larawebkit-settings';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([ManageSettings::class]);
    }

    public function boot(Panel $panel): void {}
}

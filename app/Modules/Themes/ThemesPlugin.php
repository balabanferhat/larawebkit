<?php

namespace App\Modules\Themes;

use App\Modules\Themes\Filament\Pages\ManageAppearance;
use App\Modules\Themes\Filament\Pages\ManageThemes;
use Filament\Contracts\Plugin;
use Filament\Panel;

class ThemesPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'larawebkit-themes';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([ManageThemes::class, ManageAppearance::class]);
    }

    public function boot(Panel $panel): void {}
}

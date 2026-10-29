<?php

namespace App\Modules\Seo;

use App\Modules\Seo\Filament\Resources\NotFoundLogs\NotFoundLogResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class SeoPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'larawebkit-seo';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([NotFoundLogResource::class]);
    }

    public function boot(Panel $panel): void {}
}

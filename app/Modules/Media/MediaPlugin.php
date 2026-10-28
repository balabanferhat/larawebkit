<?php

namespace App\Modules\Media;

use App\Modules\Media\Filament\Resources\Media\MediaResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class MediaPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'larawebkit-media';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([MediaResource::class]);
    }

    public function boot(Panel $panel): void {}
}

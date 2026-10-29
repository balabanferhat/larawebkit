<?php

namespace App\Modules\Events;

use App\Modules\Events\Filament\Resources\Events\EventResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class EventsPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'larawebkit-events';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([EventResource::class]);
    }

    public function boot(Panel $panel): void {}
}

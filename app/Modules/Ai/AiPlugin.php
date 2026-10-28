<?php

namespace App\Modules\Ai;

use App\Modules\Ai\Filament\Resources\AiUsages\AiUsageResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class AiPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'larawebkit-ai';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([AiUsageResource::class]);
    }

    public function boot(Panel $panel): void {}
}

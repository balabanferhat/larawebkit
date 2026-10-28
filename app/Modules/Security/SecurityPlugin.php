<?php

namespace App\Modules\Security;

use App\Modules\Security\Filament\Pages\ActiveSessions;
use App\Modules\Security\Filament\Resources\Roles\RoleResource;
use App\Modules\Security\Filament\Resources\Users\UserResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class SecurityPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'larawebkit-security';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([UserResource::class, RoleResource::class])
            ->pages([ActiveSessions::class]);
    }

    public function boot(Panel $panel): void {}
}

<?php

namespace App\Modules\Members;

use App\Modules\Members\Filament\Resources\Members\MemberResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class MembersPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'larawebkit-members';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([MemberResource::class]);
    }

    public function boot(Panel $panel): void {}
}

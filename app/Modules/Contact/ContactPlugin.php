<?php

namespace App\Modules\Contact;

use App\Modules\Contact\Filament\Resources\ContactMessages\ContactMessageResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class ContactPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'larawebkit-contact';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([ContactMessageResource::class]);
    }

    public function boot(Panel $panel): void {}
}

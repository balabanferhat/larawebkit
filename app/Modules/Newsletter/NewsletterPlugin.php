<?php

namespace App\Modules\Newsletter;

use App\Modules\Newsletter\Filament\Resources\Subscribers\SubscriberResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class NewsletterPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'larawebkit-newsletter';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([SubscriberResource::class]);
    }

    public function boot(Panel $panel): void {}
}

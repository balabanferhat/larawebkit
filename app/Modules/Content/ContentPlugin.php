<?php

namespace App\Modules\Content;

use App\Modules\Content\Filament\Resources\Categories\CategoryResource;
use App\Modules\Content\Filament\Resources\Menus\MenuResource;
use App\Modules\Content\Filament\Resources\Pages\PageResource;
use App\Modules\Content\Filament\Resources\Posts\PostResource;
use App\Modules\Content\Filament\Resources\Redirects\RedirectResource;
use App\Modules\Content\Filament\Resources\Tags\TagResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class ContentPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'larawebkit-content';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            PostResource::class, PageResource::class, CategoryResource::class, TagResource::class,
            MenuResource::class, RedirectResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}
}

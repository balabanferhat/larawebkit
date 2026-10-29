<?php

namespace App\Modules\Forms;

use App\Modules\Forms\Filament\Resources\Forms\FormResource;
use App\Modules\Forms\Filament\Resources\FormSubmissions\FormSubmissionResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FormsPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'larawebkit-forms';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([FormResource::class, FormSubmissionResource::class]);
    }

    public function boot(Panel $panel): void {}
}

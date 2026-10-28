<?php

namespace App\Modules\Content\Filament\Resources\Menus\Pages;

use App\Modules\Content\Filament\Resources\Menus\MenuResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMenus extends ListRecords
{
    protected static string $resource = MenuResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}

<?php

namespace App\Modules\Content\Filament\Resources\Pages\Pages;

use App\Modules\Content\Filament\ContentActions;
use App\Modules\Content\Filament\Resources\Pages\PageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return ContentActions::tabs($this);
    }
}

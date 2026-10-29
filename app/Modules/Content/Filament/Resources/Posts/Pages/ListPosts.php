<?php

namespace App\Modules\Content\Filament\Resources\Posts\Pages;

use App\Modules\Content\Filament\ContentActions;
use App\Modules\Content\Filament\Resources\Posts\PostResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        return ContentActions::tabs($this);
    }
}

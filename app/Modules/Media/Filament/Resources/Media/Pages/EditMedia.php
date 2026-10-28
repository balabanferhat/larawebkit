<?php

namespace App\Modules\Media\Filament\Resources\Media\Pages;

use App\Modules\Media\Filament\Resources\Media\MediaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMedia extends EditRecord
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}

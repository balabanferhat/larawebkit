<?php

namespace App\Modules\Content\Filament\Resources\Pages\Pages;

use App\Modules\Content\Filament\Resources\Pages\PageResource;
use App\Modules\Editor\Concerns\InteractsWithContentEditor;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    use InteractsWithContentEditor;

    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->enforcePublishGate($data, null);

        return $this->prepareContentData($data, null);
    }

    protected function afterCreate(): void
    {
        $this->logGateOverride($this->getRecord());
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}

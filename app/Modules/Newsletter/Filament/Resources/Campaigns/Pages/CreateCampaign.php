<?php

namespace App\Modules\Newsletter\Filament\Resources\Campaigns\Pages;

use App\Modules\Newsletter\Filament\Resources\Campaigns\CampaignResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCampaign extends CreateRecord
{
    protected static string $resource = CampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + ['created_by' => auth()->id(), 'status' => 'draft'];
    }

    protected function getRedirectUrl(): string
    {
        return CampaignResource::getUrl('edit', ['record' => $this->record]);
    }
}

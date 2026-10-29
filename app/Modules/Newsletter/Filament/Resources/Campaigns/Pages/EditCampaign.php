<?php

namespace App\Modules\Newsletter\Filament\Resources\Campaigns\Pages;

use App\Modules\Newsletter\Filament\Resources\Campaigns\CampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [...CampaignResource::campaignActions(), DeleteAction::make()->visible(fn () => $this->record->status !== 'sending')];
    }

    protected function getFormActions(): array
    {
        return $this->record->isEditable() ? parent::getFormActions() : [];
    }
}

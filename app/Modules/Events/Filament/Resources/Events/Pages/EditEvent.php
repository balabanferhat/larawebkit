<?php

namespace App\Modules\Events\Filament\Resources\Events\Pages;

use App\Modules\Events\Filament\Resources\Events\EventResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view')->label(__('larawebkit-content.view'))->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                ->url(fn () => $this->record->publicPath(), shouldOpenInNewTab: true)->visible(fn () => $this->record->status !== 'draft'),
            EventResource::sendLinkAction(),
            DeleteAction::make(),
        ];
    }
}

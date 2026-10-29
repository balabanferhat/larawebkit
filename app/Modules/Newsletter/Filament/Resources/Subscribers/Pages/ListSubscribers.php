<?php

namespace App\Modules\Newsletter\Filament\Resources\Subscribers\Pages;

use App\Modules\Newsletter\Filament\Resources\Subscribers\SubscriberResource;
use App\Modules\Newsletter\SubscriberExport;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListSubscribers extends ListRecords
{
    protected static string $resource = SubscriberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')->label(__('larawebkit-newsletter.export'))->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(fn () => SubscriberExport::csv()),
        ];
    }
}

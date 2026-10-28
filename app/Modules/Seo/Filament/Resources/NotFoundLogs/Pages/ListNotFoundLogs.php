<?php

namespace App\Modules\Seo\Filament\Resources\NotFoundLogs\Pages;

use App\Modules\Seo\Filament\Resources\NotFoundLogs\NotFoundLogResource;
use Filament\Resources\Pages\ListRecords;

class ListNotFoundLogs extends ListRecords
{
    protected static string $resource = NotFoundLogResource::class;
}

<?php

namespace App\Modules\Logs\Filament\Resources\AuthLogs\Pages;

use App\Modules\Logs\Filament\Resources\AuthLogs\AuthLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAuthLogs extends ListRecords
{
    protected static string $resource = AuthLogResource::class;
}

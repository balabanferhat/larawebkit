<?php

namespace App\Modules\Logs\Filament\Resources\AccessLogs\Pages;

use App\Modules\Logs\Filament\Resources\AccessLogs\AccessLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAccessLogs extends ListRecords
{
    protected static string $resource = AccessLogResource::class;
}

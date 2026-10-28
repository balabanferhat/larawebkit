<?php

namespace App\Modules\Logs\Filament\Resources\AuditLogs\Pages;

use App\Modules\Logs\Filament\Resources\AuditLogs\AuditLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;
}

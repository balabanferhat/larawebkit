<?php

namespace App\Modules\Logs\Filament\RelationManagers;

use App\Modules\Logs\Filament\Resources\AuditLogs\AuditLogResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * "History" tab for any resource whose model uses the Auditable trait.
 */
class AuditLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'auditLogs';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('larawebkit-logs.history');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('view logs') ?? false;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function infolist(Schema $schema): Schema
    {
        return AuditLogResource::infolist($schema);
    }

    public function table(Table $table): Table
    {
        return AuditLogResource::auditTable($table, withModel: false);
    }
}

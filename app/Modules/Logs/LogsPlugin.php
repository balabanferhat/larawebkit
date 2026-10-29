<?php

namespace App\Modules\Logs;

use App\Modules\Logs\Filament\Pages\AiBotsReport;
use App\Modules\Logs\Filament\Resources\AccessLogs\AccessLogResource;
use App\Modules\Logs\Filament\Resources\AuditLogs\AuditLogResource;
use App\Modules\Logs\Filament\Resources\AuthLogs\AuthLogResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class LogsPlugin implements Plugin
{
    public static function make(): static
    {
        return new static;
    }

    public function getId(): string
    {
        return 'larawebkit-logs';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->resources([AuditLogResource::class, AuthLogResource::class, AccessLogResource::class])
            ->pages([AiBotsReport::class]);
    }

    public function boot(Panel $panel): void {}
}

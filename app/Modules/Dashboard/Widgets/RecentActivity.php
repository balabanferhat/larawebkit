<?php

namespace App\Modules\Dashboard\Widgets;

use App\Modules\Logs\Models\AuditLog;
use App\Modules\Logs\Models\AuthLog;
use Filament\Widgets\Widget;

class RecentActivity extends Widget
{
    protected static ?int $sort = 5;

    protected string $view = 'admin.widgets.recent-activity';

    public static function canView(): bool
    {
        return auth()->user()?->can('view logs') ?? false;
    }

    protected function getViewData(): array
    {
        return [
            'audit' => AuditLog::with('user')->latest('id')->limit(8)->get(),
            'failed' => AuthLog::whereIn('event', ['failed', 'lockout', 'two_factor_failed'])->latest('id')->limit(5)->get(),
        ];
    }
}

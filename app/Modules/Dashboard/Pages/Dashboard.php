<?php

namespace App\Modules\Dashboard\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/** Two-column dashboard: welcome + stats on top, then the widgets side by side. */
class Dashboard extends BaseDashboard
{
    public function getColumns(): int|array
    {
        return ['md' => 2];
    }
}

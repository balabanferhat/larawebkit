<?php

namespace App\Modules\Events;

use App\Modules\Core\ModuleRegistry;

final class Events
{
    public static function enabled(): bool
    {
        return ModuleRegistry::enabled('events') && (bool) setting('features.events', false);
    }
}

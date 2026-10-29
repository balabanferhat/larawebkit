<?php

namespace App\Modules\Core;

use Filament\Contracts\Plugin;

/**
 * Reads config/larawebkit.php and exposes the enabled modules.
 */
class ModuleRegistry
{
    /** @return array<string, array{provider: ?class-string, plugin: ?class-string}> */
    public static function modules(): array
    {
        return (array) config('larawebkit.modules', []);
    }

    public static function enabled(string $module): bool
    {
        return array_key_exists($module, self::modules());
    }

    /** @return list<class-string> */
    public static function providers(): array
    {
        return array_values(array_filter(array_column(self::modules(), 'provider')));
    }

    /** @return list<Plugin> */
    public static function adminPlugins(): array
    {
        return array_map(
            fn (string $class) => $class::make(),
            array_values(array_filter(array_column(self::modules(), 'plugin'))),
        );
    }
}

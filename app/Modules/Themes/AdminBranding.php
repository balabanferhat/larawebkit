<?php

namespace App\Modules\Themes;

use App\Modules\Core\ModuleRegistry;
use App\Modules\Media\Models\Media;
use App\Modules\Themes\Design\DesignTokens;
use App\Modules\Themes\Design\Favicons;
use Filament\Support\Colors\Color;
use Throwable;

/**
 * The admin panel uses the same brand as the site (Appearance): logo, dark-mode
 * logo, favicon and primary colour. Falls back to Filament defaults when the
 * Themes module is dropped or nothing is configured.
 */
class AdminBranding
{
    public static function logo(bool $dark = false): ?string
    {
        return self::guard(function () use ($dark) {
            $id = setting($dark ? 'appearance.logo_dark' : 'appearance.logo') ?: ($dark ? setting('appearance.logo') : null);

            return $id ? Media::find($id)?->url(480) : null;
        });
    }

    public static function favicon(): ?string
    {
        return self::guard(fn () => app(Favicons::class)->urls()['icon-32.png'] ?? null) ?? asset('favicon.ico');
    }

    /** @return array<string, array<int, string>> */
    public static function colors(): array
    {
        $primary = self::guard(fn () => app(DesignTokens::class)->values()['primary']);

        return ['primary' => $primary ? Color::hex($primary) : Color::Indigo];
    }

    private static function guard(callable $callback): mixed
    {
        if (! ModuleRegistry::enabled('themes')) {
            return null;
        }
        try {
            return $callback();
        } catch (Throwable) {
            return null;
        }
    }
}

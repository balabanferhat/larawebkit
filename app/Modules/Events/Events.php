<?php

namespace App\Modules\Events;

use App\Modules\Core\ModuleRegistry;

final class Events
{
    /** URL segment of the events pages in a language (config larawebkit-events.path / paths). */
    public static function path(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $path = (string) (config('larawebkit-events.paths.'.$locale) ?: config('larawebkit-events.path', 'events'));

        return trim($path, '/') ?: 'events';
    }

    /** The localized listing: /events, /en/events, /etkinlikler... */
    public static function indexPath(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return localized_path('/'.self::path($locale), $locale);
    }

    /** @return list<string> every path in use, the main one first */
    public static function paths(): array
    {
        return array_values(array_unique([trim((string) config('larawebkit-events.path', 'events'), '/') ?: 'events', ...array_map(fn ($p) => trim((string) $p, '/'), array_values((array) config('larawebkit-events.paths', [])))]));
    }

    public static function enabled(): bool
    {
        return ModuleRegistry::enabled('events') && (bool) setting('features.events', false);
    }
}

<?php

namespace App\Modules\Settings;

use App\Modules\Settings\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

/**
 * Typed key/value settings stored in the `settings` table and cached forever
 * (busted on write). Definitions live in config/larawebkit-settings.php.
 */
class SettingsRepository
{
    public const CACHE_KEY = 'larawebkit.settings';

    /** @var array<string, mixed>|null */
    private ?array $loaded = null;

    /** @return array<string, array<string, mixed>> */
    public function definitions(): array
    {
        return (array) config('larawebkit-settings', []);
    }

    /** @return array<string, mixed>|null */
    public function definition(string $key): ?array
    {
        return $this->definitions()[$key] ?? null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->stored();
        $definition = $this->definition($key);

        if (array_key_exists($key, $values)) {
            return $this->cast($values[$key], $definition['type'] ?? 'string');
        }

        return $default ?? ($definition['default'] ?? null);
    }

    /** Whether a value was saved (false = still the default from config). */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->stored());
    }

    public function set(string $key, mixed $value): void
    {
        $definition = $this->definition($key);
        if ($definition === null) {
            throw new InvalidArgumentException("Unknown setting [$key]. Define it in config/larawebkit-settings.php.");
        }

        $setting = Setting::firstOrNew(['key' => $key]);
        $setting->value = $this->cast($value, $definition['type']);
        $setting->save();

        $this->flush();
    }

    /** @param  array<string, mixed>  $values */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    /** @return array<string, mixed> All settings with defaults applied. */
    public function all(): array
    {
        $out = [];
        foreach (array_keys($this->definitions()) as $key) {
            $out[$key] = $this->get($key);
        }

        return $out;
    }

    public function flush(): void
    {
        $this->loaded = null;
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array<string, mixed> */
    private function stored(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            return $this->loaded = Cache::rememberForever(self::CACHE_KEY, function () {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return Setting::query()->pluck('value', 'key')->all();
            });
        } catch (Throwable) {
            // No database yet (fresh clone, install step): fall back to defaults.
            return $this->loaded = [];
        }
    }

    private function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => $value === null || $value === '' ? null : (int) $value,
            'media' => $value === null || $value === '' ? null : (int) $value,
            'multiselect', 'map', 'bots' => is_array($value) ? $value : (array) json_decode((string) $value, true),
            default => $value === null ? '' : (string) $value,
        };
    }
}

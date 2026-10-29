<?php

use App\Modules\Settings\SettingsRepository;

if (! function_exists('setting')) {
    /**
     * Read a typed setting, e.g. setting('general.site_name').
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingsRepository::class)->get($key, $default);
    }
}

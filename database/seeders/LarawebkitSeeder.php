<?php

namespace Database\Seeders;

use App\Modules\Content\DefaultContent;
use App\Modules\Core\ModuleRegistry;
use App\Modules\Security\RolesAndPermissions;
use App\Modules\Settings\SettingsRepository;
use Illuminate\Database\Seeder;

/**
 * Idempotent defaults: roles/permissions and, when the content module is
 * enabled, header/footer menus plus privacy & cookie policy page stubs.
 */
class LarawebkitSeeder extends Seeder
{
    public function run(): void
    {
        app(RolesAndPermissions::class)->sync();

        // Default pages and the contact form are written in the site language: choose it first.
        if (! app(SettingsRepository::class)->has('general.default_locale')) {
            $this->command?->warn('No site language chosen yet: default pages are created in "'.setting('general.default_locale', 'en').'". Run php artisan larawebkit:install (or set Settings → General → language) before seeding.');
        }

        if (ModuleRegistry::enabled('content') && class_exists(DefaultContent::class)) {
            app(DefaultContent::class)->seed();
        }
    }
}

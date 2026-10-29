<?php

namespace Database\Seeders;

use App\Modules\Content\DefaultContent;
use App\Modules\Core\ModuleRegistry;
use App\Modules\Security\RolesAndPermissions;
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

        if (ModuleRegistry::enabled('content') && class_exists(DefaultContent::class)) {
            app(DefaultContent::class)->seed();
        }
    }
}

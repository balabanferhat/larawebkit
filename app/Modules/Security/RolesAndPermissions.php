<?php

namespace App\Modules\Security;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Single source of truth for seeded roles and permissions. Idempotent:
 * run it from the seeder, the install command or a test.
 * super-admin bypasses every check (Gate::before in SecurityServiceProvider).
 */
class RolesAndPermissions
{
    public const PERMISSIONS = [
        'view dashboard',
        'manage settings',
        'manage admins',
        'manage roles',
        'view logs',
        'manage pages',
        'manage posts',
        'manage own posts',
        'publish content',
        'override publish gate',
        'manage taxonomies',
        'manage media',
        'delete media',
        'manage menus',
        'manage redirects',
        'use ai',
        'manage forms',
        'manage newsletter',
    ];

    public const ROLES = [
        'super-admin' => ['*'],
        'editor' => [
            'view dashboard', 'manage pages', 'manage posts', 'manage own posts', 'publish content', 'override publish gate',
            'manage taxonomies', 'manage media', 'delete media', 'manage menus', 'manage redirects', 'use ai',
            'manage forms', 'manage newsletter', 'view logs',
        ],
        'author' => ['view dashboard', 'manage own posts', 'manage media', 'use ai'],
    ];

    public function sync(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (self::ROLES as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions === ['*'] ? self::PERMISSIONS : $permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

<?php

use App\Modules\Security\RolesAndPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * v0.2 permissions on sites installed with v0.1: "manage contact messages" becomes "manage forms" (custom
 * roles keep it), and the new ones (members, events) are created and given to the built-in roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! DB::table('permissions')->exists()) {
            return; // fresh install: larawebkit:install syncs them
        }
        if (! DB::table('permissions')->where('name', 'manage forms')->exists()) {
            DB::table('permissions')->where('name', 'manage contact messages')->update(['name' => 'manage forms']);
        }
        app(RolesAndPermissions::class)->sync();
    }

    public function down(): void
    {
        // Nothing to undo: the permissions stay.
    }
};

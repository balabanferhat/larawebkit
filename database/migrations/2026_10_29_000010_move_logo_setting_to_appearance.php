<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** v0.2: the logo moved from Settings → General to Appearance. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings') && ! DB::table('settings')->where('key', 'appearance.logo')->exists()) {
            DB::table('settings')->where('key', 'general.logo')->update(['key' => 'appearance.logo']);
        }
        DB::table('settings')->where('key', 'general.logo')->delete();
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'appearance.logo')->update(['key' => 'general.logo']);
    }
};

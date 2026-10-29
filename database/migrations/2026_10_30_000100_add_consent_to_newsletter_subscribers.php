<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Which consent text a subscriber accepted, and when (Settings → Newsletter → require consent). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->unsignedInteger('consent_version')->nullable();
            $table->text('consent_text')->nullable();
            $table->timestamp('consented_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->dropColumn(['consent_version', 'consent_text', 'consented_at']);
        });
    }
};

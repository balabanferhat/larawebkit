<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('slug')->nullable()->unique();
            $table->string('job_title')->nullable();
            $table->text('bio')->nullable();
            $table->string('expertise')->nullable();
            $table->unsignedBigInteger('avatar_media_id')->nullable();
            $table->json('same_as')->nullable();
            $table->string('locale', 10)->nullable();
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['app_authentication_secret', 'app_authentication_recovery_codes', 'is_active', 'slug', 'job_title', 'bio', 'expertise', 'avatar_media_id', 'same_as', 'locale', 'last_login_at']);
        });
    }
};

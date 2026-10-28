<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event', 40);
            $table->string('auditable_type');
            $table->string('auditable_id', 64);
            $table->string('auditable_label')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at')->index();
            $table->index(['auditable_type', 'auditable_id']);
        });

        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->date('day')->index();
            $table->string('method', 10);
            $table->string('path', 500)->index();
            $table->unsignedSmallInteger('status');
            $table->string('referrer_host')->nullable()->index();
            $table->string('referrer', 500)->nullable();
            $table->string('agent_class', 20)->index();
            $table->string('bot_name', 60)->nullable()->index();
            $table->string('ip_hash', 64)->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamp('created_at')->index();
        });

        Schema::create('auth_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event', 40)->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->index();
        });

        Schema::create('not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500)->unique();
            $table->unsignedInteger('hits')->default(0);
            $table->string('last_referrer', 500)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_found_logs');
        Schema::dropIfExists('auth_logs');
        Schema::dropIfExists('access_logs');
        Schema::dropIfExists('audit_logs');
    }
};

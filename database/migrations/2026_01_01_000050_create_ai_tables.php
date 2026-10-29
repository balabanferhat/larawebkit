<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usages', function (Blueprint $table) {
            $table->id();
            $table->string('action', 60)->index();
            $table->string('provider', 30);
            $table->string('model', 100);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('cost_usd', 10, 6)->default(0);
            $table->boolean('succeeded')->default(true);
            $table->foreignId('user_id')->nullable()->index();
            $table->timestamp('created_at')->index();
        });

        Schema::create('ai_tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('action', 60);
            $table->string('status', 20)->default('pending')->index();
            $table->json('input');
            $table->longText('output')->nullable();
            $table->string('error', 1000)->nullable();
            $table->foreignId('user_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tasks');
        Schema::dropIfExists('ai_usages');
    }
};

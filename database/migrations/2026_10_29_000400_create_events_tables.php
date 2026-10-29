<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** v0.2 (optional Events module): events with free registration for members and guests. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->string('locale', 10)->default('en')->index();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->unsignedInteger('capacity')->nullable();
            $table->string('location_type', 10)->default('online');
            $table->string('venue_name')->nullable();
            $table->text('address')->nullable();
            $table->text('online_url')->nullable();
            $table->boolean('registration_open')->default(true);
            $table->foreignId('featured_media_id')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamps();
            $table->unique(['locale', 'slug']);
        });

        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->index();
            $table->string('name');
            $table->string('email');
            $table->string('status', 20)->default('registered');
            $table->timestamp('link_sent_at')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('events');
    }
};

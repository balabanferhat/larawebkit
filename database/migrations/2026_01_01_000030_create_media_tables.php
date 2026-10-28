<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('disk', 40);
            $table->string('directory');
            $table->string('original_disk', 40);
            $table->string('original_path')->nullable();
            $table->string('file_name');
            $table->string('title')->nullable();
            $table->string('alt', 500)->nullable();
            $table->text('caption')->nullable();
            $table->string('mime_type', 100);
            $table->string('format', 10);
            $table->unsignedInteger('width')->default(0);
            $table->unsignedInteger('height')->default(0);
            $table->unsignedBigInteger('size')->default(0);
            $table->json('variants')->nullable();
            $table->string('source', 20)->default('upload');
            $table->string('source_id')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->string('photographer')->nullable();
            $table->string('photographer_url', 500)->nullable();
            $table->foreignId('uploaded_by')->nullable()->index();
            $table->timestamps();
            $table->index(['source', 'source_id']);
        });

        Schema::create('media_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->morphs('usable');
            $table->string('context', 20);
            $table->timestamps();
            $table->unique(['media_id', 'usable_type', 'usable_id', 'context']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_usages');
        Schema::dropIfExists('media');
    }
};

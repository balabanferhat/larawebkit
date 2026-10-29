<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $contentColumns = function (Blueprint $table) {
            $table->string('title');
            $table->string('slug');
            $table->string('locale', 10)->default('en')->index();
            $table->uuid('translation_group')->nullable()->index();
            $table->longText('content')->nullable();
            $table->text('summary')->nullable();
            $table->json('faq')->nullable();
            $table->text('sources')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->json('keywords')->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->boolean('noindex')->default(false);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('announced_at')->nullable();
            $table->foreignId('author_id')->nullable()->index();
            $table->foreignId('featured_media_id')->nullable()->index();
            $table->unsignedTinyInteger('seo_score')->nullable();
            $table->unsignedTinyInteger('human_score')->nullable();
            $table->unsignedTinyInteger('geo_score')->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->string('gate_override_reason', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();
        };

        Schema::create('pages', function (Blueprint $table) use ($contentColumns) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->index();
            $table->string('path', 500)->index();
            $table->string('template', 40)->default('default');
            $table->unsignedInteger('sort_order')->default(0);
            $contentColumns($table);
            $table->unique(['locale', 'path']);
        });

        Schema::create('posts', function (Blueprint $table) use ($contentColumns) {
            $table->id();
            $table->text('excerpt')->nullable();
            $table->boolean('members_only')->default(false);
            $contentColumns($table);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('category_post', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'post_id']);
        });

        Schema::create('post_tag', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['post_id', 'tag_id']);
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path', 500)->unique();
            $table->string('to_url', 500)->nullable();
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_automatic')->default(false);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location', 40)->unique();
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->index();
            $table->string('label');
            $table->string('url', 500)->nullable();
            $table->nullableMorphs('linkable');
            $table->boolean('new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('revisionable');
            $table->foreignId('user_id')->nullable();
            $table->json('data');
            $table->boolean('is_autosave')->default(false);
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        foreach (['revisions', 'menu_items', 'menus', 'redirects', 'post_tag', 'category_post', 'tags', 'categories', 'posts', 'pages'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

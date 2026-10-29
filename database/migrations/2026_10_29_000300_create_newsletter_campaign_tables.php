<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** v0.2: newsletter campaigns, sent in small batches through the database queue, with a send log. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->string('preheader')->nullable();
            $table->longText('body');
            $table->string('locale', 10)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('newsletter_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('newsletter_campaigns')->cascadeOnDelete();
            $table->foreignId('subscriber_id')->nullable()->index();
            $table->string('email');
            $table->string('status', 20)->default('pending');
            $table->string('error', 500)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unique(['campaign_id', 'subscriber_id']);
            $table->index(['campaign_id', 'status']);
        });

        Schema::table('newsletter_subscribers', function (Blueprint $table) {
            $table->foreignId('unsubscribed_campaign_id')->nullable()->after('unsubscribed_at');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_subscribers', fn (Blueprint $table) => $table->dropColumn('unsubscribed_campaign_id'));
        Schema::dropIfExists('newsletter_sends');
        Schema::dropIfExists('newsletter_campaigns');
    }
};

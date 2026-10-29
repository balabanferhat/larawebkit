<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * v0.2: admin-defined forms and their submissions. The v0.1 contact form becomes the seeded
 * "contact" form; its messages move into form_submissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 80)->unique();
            $table->json('fields');
            $table->string('submit_label')->nullable();
            $table->text('success_message')->nullable();
            $table->string('notify_emails', 500)->nullable();
            $table->boolean('send_confirmation')->default(false);
            $table->string('confirmation_subject')->nullable();
            $table->text('confirmation_body')->nullable();
            $table->string('download_path', 500)->nullable();
            $table->string('download_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->json('data');
            $table->json('files')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('status', 20)->default('new')->index();
            $table->text('notes')->nullable();
            $table->unsignedInteger('consent_version')->nullable();
            $table->text('consent_text')->nullable();
            $table->string('locale', 10)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->timestamps();
        });

        // Existing sites keep their messages; fresh installs get a translated contact form from the seeder.
        if (Schema::hasTable('contact_messages') && DB::table('contact_messages')->exists()) {
            $form = DB::table('forms')->insertGetId([
                'name' => 'Contact', 'slug' => 'contact', 'fields' => json_encode(self::contactFields()), 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (DB::table('contact_messages')->orderBy('id')->cursor() as $m) {
                DB::table('form_submissions')->insert([
                    'form_id' => $form,
                    'data' => json_encode(['name' => $m->name, 'email' => $m->email, 'subject' => $m->subject, 'message' => $m->message]),
                    'email' => $m->email, 'status' => $m->read_at ? 'read' : 'new', 'ip_hash' => $m->ip_hash,
                    'created_at' => $m->created_at, 'updated_at' => $m->updated_at,
                ]);
            }
        }
        Schema::dropIfExists('contact_messages');
        DB::table('settings')->where('key', 'features.contact_form')->delete();
    }

    public function down(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('subject')->nullable();
            $table->text('message');
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::dropIfExists('form_submissions');
        Schema::dropIfExists('forms');
    }

    /** @return list<array<string, mixed>> */
    public static function contactFields(): array
    {
        return [
            ['key' => 'name', 'type' => 'text', 'label' => 'Your name', 'required' => true, 'width' => 'half', 'max' => 120],
            ['key' => 'email', 'type' => 'email', 'label' => 'Your e-mail', 'required' => true, 'width' => 'half'],
            ['key' => 'subject', 'type' => 'text', 'label' => 'Subject', 'required' => false, 'width' => 'full', 'max' => 200],
            ['key' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true, 'width' => 'full', 'max' => 5000],
        ];
    }
};

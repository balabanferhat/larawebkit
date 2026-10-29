<?php

use App\Modules\Blocks\BlockForms;
use App\Modules\Content\DefaultContent;
use App\Modules\Content\Models\Page;
use App\Modules\Forms\Filament\Resources\Forms\Pages\CreateForm;
use App\Modules\Forms\Filament\Resources\FormSubmissions\Pages\ViewFormSubmission;
use App\Modules\Forms\Mail\FormConfirmation;
use App\Modules\Forms\Mail\FormSubmissionReceived;
use App\Modules\Forms\Models\Form;
use App\Modules\Forms\Models\FormSubmission;
use App\Modules\Forms\SubmissionExport;
use App\Modules\Settings\SettingsRepository;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function honeypot(): array
{
    return ['_lwk_ts' => Crypt::encryptString((string) (time() - 10))];
}

function quoteForm(array $attributes = []): Form
{
    return Form::create($attributes + [
        'name' => 'Quote request', 'slug' => 'quote', 'notify_emails' => 'sales@example.com, team@example.com',
        'fields' => [
            ['key' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true, 'width' => 'half'],
            ['key' => 'email', 'type' => 'email', 'label' => 'E-mail', 'required' => true, 'width' => 'half'],
            ['key' => 'phone', 'type' => 'phone', 'label' => 'Phone'],
            ['key' => 'budget', 'type' => 'select', 'label' => 'Budget', 'options' => "Small\nLarge", 'required' => true],
            ['key' => 'size', 'type' => 'radio', 'label' => 'Team size', 'options' => ['1-10', '11+']],
            ['key' => 'start', 'type' => 'date', 'label' => 'Start date'],
            ['key' => 'brief', 'type' => 'file', 'label' => 'Brief', 'accept' => ['pdf'], 'max_kb' => 200],
            ['key' => 'newsletter', 'type' => 'checkbox', 'label' => 'Send me news'],
            ['key' => 'consent', 'type' => 'consent', 'label' => 'I agree to the privacy policy.', 'consent_version' => 3],
            ['key' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true, 'max' => 500],
        ],
    ]);
}

function postForm(string $slug, array $fields)
{
    return test()->withoutMiddleware(PreventRequestForgery::class)->from('/quote-page')->post("/forms/$slug", honeypot() + ['fields' => $fields]);
}

it('seeds a translated contact form and renders it on the contact page', function () {
    app()->setLocale('tr');
    app(SettingsRepository::class)->setMany(['general.default_locale' => 'tr', 'general.active_locales' => ['tr', 'en']]);
    app(DefaultContent::class)->seed();
    Page::factory()->published()->create(['slug' => 'iletisim', 'template' => 'contact', 'locale' => 'tr']);

    expect(Form::where('slug', 'contact')->first()->fieldList()[0]['label'])->toBe('Adınız');
    $this->get('/iletisim')->assertOk()->assertSee('name="fields[message]"', false)->assertSee('name="website_url"', false);
});

it('stores a valid submission with its file and e-mails the team and the sender', function () {
    Mail::fake();
    Storage::fake('local');
    quoteForm(['send_confirmation' => true]);

    postForm('quote', [
        'name' => 'Ann', 'email' => 'Ann@Example.com', 'phone' => '+90 532 000 00 00', 'budget' => 'Large', 'size' => '11+',
        'start' => '2026-12-01', 'newsletter' => '1', 'consent' => '1', 'message' => 'We need a new site.',
        'brief' => UploadedFile::fake()->create('brief.pdf', 50, 'application/pdf'),
    ])->assertRedirect()->assertSessionHas('form_status.quote');

    $submission = FormSubmission::sole();
    expect($submission->email)->toBe('ann@example.com')
        ->and($submission->data['budget'])->toBe('Large')
        ->and($submission->data['newsletter'])->toBeTrue()
        ->and($submission->consent_version)->toBe(3)
        ->and($submission->consent_text)->toBe('I agree to the privacy policy.')
        ->and($submission->files[0]['name'])->toBe('brief.pdf');
    Storage::disk('local')->assertExists($submission->files[0]['path']);

    Mail::assertQueued(FormSubmissionReceived::class, fn ($m) => $m->hasTo('sales@example.com') && $m->hasTo('team@example.com') && $m->hasReplyTo('ann@example.com'));
    Mail::assertQueued(FormConfirmation::class, fn ($m) => $m->hasTo('ann@example.com'));

    // Deleting a submission removes its files.
    $path = $submission->files[0]['path'];
    $submission->delete();
    Storage::disk('local')->assertMissing($path);
});

it('validates every field type', function () {
    quoteForm();

    postForm('quote', ['email' => 'not-an-email', 'phone' => '<script>', 'budget' => 'Huge', 'start' => 'tomorrow-ish',
        'brief' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php')])
        ->assertSessionHasErrors(['fields.name', 'fields.email', 'fields.phone', 'fields.budget', 'fields.start', 'fields.brief', 'fields.consent', 'fields.message']);
    expect(FormSubmission::count())->toBe(0);
});

it('refuses files that are too large', function () {
    quoteForm();

    postForm('quote', ['name' => 'A', 'email' => 'a@example.com', 'budget' => 'Small', 'consent' => '1', 'message' => 'Hi',
        'brief' => UploadedFile::fake()->create('brief.pdf', 500, 'application/pdf')])->assertSessionHasErrors(['fields.brief']);
});

it('is protected by the honeypot and the rate limit', function () {
    quoteForm();
    $valid = ['name' => 'A', 'email' => 'a@example.com', 'budget' => 'Small', 'consent' => '1', 'message' => 'Hi'];

    $this->withoutMiddleware(PreventRequestForgery::class)->post('/forms/quote', honeypot() + ['website_url' => 'spam', 'fields' => $valid])->assertSessionHas('status');
    expect(FormSubmission::count())->toBe(0);

    config(['larawebkit-security.public_form_rate_limit' => 2]);
    postForm('quote', $valid);
    postForm('quote', $valid);
    postForm('quote', $valid)->assertSessionHasErrors();
    expect(FormSubmission::count())->toBe(2);
});

it('sends a signed, expiring download link for a lead magnet', function () {
    Mail::fake();
    Storage::fake('local');
    Storage::disk('local')->put('downloads/guide.pdf', '%PDF-1.4 guide');
    Form::create(['name' => 'Guide', 'slug' => 'guide', 'download_path' => 'downloads/guide.pdf', 'download_name' => 'Website guide.pdf',
        'fields' => [['key' => 'email', 'type' => 'email', 'label' => 'E-mail', 'required' => true]]]);

    postForm('guide', ['email' => 'reader@example.com'])->assertSessionHas('form_status.guide');

    $link = null;
    Mail::assertQueued(FormConfirmation::class, function (FormConfirmation $mail) use (&$link) {
        $link = $mail->content()->with['download'];

        return $mail->hasTo('reader@example.com');
    });
    $this->get($link)->assertOk()->assertDownload('Website guide.pdf');
    $this->get(str_replace('signature=', 'signature=x', $link))->assertForbidden();
    $this->travel(4)->days();
    $this->get($link)->assertForbidden();
});

it('exports submissions as CSV and neutralises formulas', function () {
    $form = quoteForm();
    $form->submissions()->create(['data' => ['name' => '=HYPERLINK("http://evil")', 'email' => 'a@example.com', 'budget' => 'Small', 'message' => 'Şeker'], 'email' => 'a@example.com']);

    ob_start();
    SubmissionExport::csv($form)->sendContent();
    $csv = (string) ob_get_clean();

    expect($csv)->toStartWith("\xEF\xBB\xBF")->toContain("'=HYPERLINK")->toContain('Şeker')->toContain('Budget');
});

it('builds forms in the admin and shows submissions with status and notes', function () {
    $this->actingAs(superAdmin());

    Livewire::test(CreateForm::class)
        ->fillForm(['name' => 'Feedback', 'slug' => 'feedback', 'is_active' => true, 'fields' => [['type' => 'textarea', 'label' => 'Your feedback', 'key' => 'feedback', 'required' => true, 'width' => 'full']]])
        ->call('create')->assertHasNoFormErrors();
    $form = Form::where('slug', 'feedback')->sole();
    $submission = $form->submissions()->create(['data' => ['feedback' => 'Great'], 'status' => 'new']);

    Livewire::test(ViewFormSubmission::class, ['record' => $submission->id])->assertSee('Great')
        ->callAction('status', ['status' => 'archived', 'notes' => 'Handled by phone']);
    expect($submission->fresh())->status->toBe('archived')->notes->toBe('Handled by phone');
});

it('embeds any active form in a page block', function () {
    quoteForm();
    Page::factory()->create(['slug' => 'quote-page', 'status' => 'published', 'published_at' => now(), 'blocks' => [
        ['type' => 'form', 'data' => ['heading' => 'Ask for a quote', 'form' => 'quote']],
    ]]);

    $this->get('/quote-page')->assertOk()->assertSee('Ask for a quote')->assertSee('action="'.route('forms.submit', 'quote').'"', false)
        ->assertSee('enctype="multipart/form-data"', false);
    expect(BlockForms::options())->toHaveKey('quote');
});

it('moves v0.1 contact messages into the contact form', function () {
    $migration = require database_path('migrations/2026_10_29_000100_create_forms_tables.php');
    $migration->down();
    DB::table('contact_messages')->insert(['name' => 'Old', 'email' => 'old@example.com', 'message' => 'From v0.1', 'created_at' => now(), 'updated_at' => now()]);
    $migration->up();

    $submission = FormSubmission::sole();
    expect($submission->form->slug)->toBe('contact')->and($submission->data['message'])->toBe('From v0.1')
        ->and(Schema::hasTable('contact_messages'))->toBeFalse();
});

it('renames the v0.1 contact permission and adds the v0.2 ones on upgrade', function () {
    superAdmin();
    $forms = Permission::findByName('manage forms', 'web');
    $forms->update(['name' => 'manage contact messages']);
    $custom = Role::create(['name' => 'support', 'guard_name' => 'web']);
    $custom->givePermissionTo('manage contact messages');
    Permission::where('name', 'manage events')->delete();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    (require database_path('migrations/2026_10_29_000600_sync_v02_permissions.php'))->up();

    expect($custom->fresh()->hasPermissionTo('manage forms'))->toBeTrue()
        ->and(Role::findByName('editor', 'web')->hasPermissionTo('manage events'))->toBeTrue();
});

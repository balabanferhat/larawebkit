<?php

use App\Modules\Logs\Models\AuditLog;
use App\Modules\Settings\Filament\Pages\ManageSettings;
use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\SettingsRepository;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

it('returns defaults when nothing is stored', function () {
    expect(setting('general.posts_per_page'))->toBe(10)
        ->and(setting('features.page_cache'))->toBeTrue();
});

it('stores typed values and busts the cache', function () {
    $settings = app(SettingsRepository::class);
    expect(setting('general.site_name'))->not->toBe('Acme');

    $settings->set('general.site_name', 'Acme');
    $settings->set('general.posts_per_page', '25');
    $settings->set('features.page_cache', '0');

    expect(setting('general.site_name'))->toBe('Acme')
        ->and(setting('general.posts_per_page'))->toBe(25)
        ->and(setting('features.page_cache'))->toBeFalse()
        ->and(Cache::has(SettingsRepository::CACHE_KEY))->toBeTrue();
});

it('rejects unknown keys', function () {
    app(SettingsRepository::class)->set('nope.nothing', 'x');
})->throws(InvalidArgumentException::class);

it('records setting changes in the audit log', function () {
    app(SettingsRepository::class)->set('general.tagline', 'Hello');
    app(SettingsRepository::class)->set('general.tagline', 'World');

    $log = AuditLog::where('auditable_type', (new Setting)->getMorphClass())->latest('id')->first();
    expect($log->event)->toBe('updated')
        ->and($log->new_values['value'])->toContain('World');
});

it('lets a super admin save settings in the admin', function () {
    $this->actingAs(superAdmin());

    Livewire::test(ManageSettings::class)
        ->fillForm(['general__site_name' => 'From admin', 'general__posts_per_page' => 7])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(setting('general.site_name'))->toBe('From admin')
        ->and(setting('general.posts_per_page'))->toBe(7);
});

it('hides settings from authors', function () {
    $this->actingAs(author())->get(ManageSettings::getUrl())->assertForbidden();
});

it('never defines secrets as settings', function () {
    $keys = implode(' ', array_keys(config('larawebkit-settings')));
    expect($keys)->not->toMatch('/api_key|secret|password|token/i');
});

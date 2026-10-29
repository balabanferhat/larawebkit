<?php

use App\Modules\Media\ImagePipeline;
use App\Modules\Settings\SettingsRepository;
use App\Modules\Themes\Design\Color;
use App\Modules\Themes\Design\DesignTokens;
use App\Modules\Themes\Design\Favicons;
use App\Modules\Themes\Filament\Pages\ManageAppearance;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('computes WCAG contrast and keeps text readable on any brand colour', function () {
    $white = Color::hex('#fff');

    expect($white->contrast(Color::hex('#000000')))->toBe(21.0)
        ->and(Color::hex('#ffeb3b')->onColor()->toHex())->toBe('#0b0d12')
        ->and(Color::hex('#1d4ed8')->onColor()->toHex())->toBe('#ffffff')
        // A light yellow brand is darkened until links reach 4.5:1 on white.
        ->and(Color::hex('#ffeb3b')->readableOn($white)->contrast($white))->toBeGreaterThanOrEqual(4.5)
        ->and(Color::hex('nope'))->toBeNull();
});

it('turns appearance settings into CSS custom properties', function () {
    app(SettingsRepository::class)->setMany([
        'general.theme' => 'business',
        'appearance.primary_color' => '#ffcc00',
        'appearance.font_pair' => 'fraunces',
        'appearance.radius' => 'none',
    ]);
    $tokens = app(DesignTokens::class);
    $light = $tokens->variables()['light'];
    $dark = $tokens->variables()['dark'];

    expect($light['--brand'])->toBe('#ffcc00')
        ->and(Color::hex($light['--on-brand'])->contrast(Color::hex('#ffcc00')))->toBeGreaterThanOrEqual(4.5)
        ->and(Color::hex($light['--link'])->contrast(Color::hex($light['--bg-soft'])))->toBeGreaterThanOrEqual(4.5)
        ->and(Color::hex($dark['--link'])->contrast(Color::hex($dark['--bg-soft'])))->toBeGreaterThanOrEqual(4.5)
        ->and($light['--radius'])->toBe('0')
        ->and($light['--font-heading'])->toStartWith('"Fraunces Variable"')
        // The accent was left empty: business theme default.
        ->and($light['--accent'])->toBe('#f59e0b');

    $this->get('/')->assertOk()
        ->assertSee('--brand:#ffcc00', false)
        ->assertSee('fonts/lwk/fraunces-latin.woff2', false)
        ->assertDontSee('fonts.googleapis.com', false);
});

it('uses the theme defaults when nothing is set', function () {
    app(SettingsRepository::class)->set('general.theme', 'magazine');
    $values = app(DesignTokens::class)->values();

    expect($values)->toMatchArray(['primary' => '#c0262d', 'font_pair' => 'editorial', 'radius' => 'none', 'color_scheme' => 'system']);
});

it('applies the default colour scheme before paint', function () {
    app(SettingsRepository::class)->set('appearance.color_scheme', 'dark');

    $this->get('/')->assertOk()->assertSee("s='dark'", false);
});

it('generates favicons, an ICO file and a web manifest from the site icon', function () {
    Storage::fake('public');
    Storage::fake('local');
    $media = app(ImagePipeline::class)->store(fakeJpeg(600, 600), 'icon.jpg');
    app(SettingsRepository::class)->set('appearance.favicon', $media->id);

    $urls = app(Favicons::class)->urls();
    expect(array_keys($urls))->toEqualCanonicalizing(['favicon.ico', 'icon-32.png', 'apple-touch-icon.png', 'icon-192.png', 'icon-512.png']);

    $dir = dirname(Storage::disk('public')->path(str_replace(Storage::disk('public')->url(''), '', $urls['icon-512.png'])));
    expect(getimagesize("$dir/icon-512.png"))->toMatchArray([0 => 512, 1 => 512, 'mime' => 'image/png'])
        ->and(getimagesize("$dir/apple-touch-icon.png")[0])->toBe(180)
        ->and(substr((string) file_get_contents("$dir/favicon.ico"), 0, 6))->toBe("\0\0\1\0\3\0");

    $this->get('/')->assertSee('apple-touch-icon', false);
    $this->get('/site.webmanifest')->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json')
        ->assertJsonPath('icons.1.sizes', '512x512')
        ->assertJsonPath('start_url', '/');
});

it('falls back to the default favicon without a site icon', function () {
    $this->get('/')->assertSee('favicon.ico', false);
    $this->get('/site.webmanifest')->assertOk()->assertJsonMissingPath('icons');
});

it('saves appearance settings from the admin page', function () {
    $this->actingAs(superAdmin());

    Livewire::test(ManageAppearance::class)
        ->fillForm(['appearance__primary_color' => '#0f766e', 'appearance__font_pair' => 'jakarta', 'header__cta_label' => 'Get a quote'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(setting('appearance.primary_color'))->toBe('#0f766e')
        ->and(setting('header.cta_label'))->toBe('Get a quote');
});

it('rejects an invalid brand colour', function () {
    $this->actingAs(superAdmin());

    Livewire::test(ManageAppearance::class)
        ->fillForm(['appearance__primary_color' => 'red; } body { display:none'])
        ->call('save')
        ->assertHasFormErrors(['appearance__primary_color']);
});

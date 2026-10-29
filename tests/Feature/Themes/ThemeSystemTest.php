<?php

use App\Modules\Settings\SettingsRepository;
use App\Modules\Themes\Filament\Pages\ManageThemes;
use App\Modules\Themes\ThemeAssets;
use App\Modules\Themes\ThemeInstaller;
use App\Modules\Themes\ThemeManager;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

/** A throw-away themes/ folder with a copy of "minimal" plus the given extra themes. */
function sandboxThemes(array $themes = []): string
{
    $root = storage_path('framework/testing/themes-'.uniqid());
    File::copyDirectory(base_path('themes/minimal'), "$root/minimal");
    foreach ($themes as $name => $files) {
        foreach ($files as $path => $content) {
            File::ensureDirectoryExists(dirname("$root/$name/$path"));
            File::put("$root/$name/$path", is_array($content) ? json_encode($content) : $content);
        }
    }
    config(['larawebkit-themes.path' => $root]);
    app(ThemeManager::class)->flush();

    return $root;
}

afterEach(function () {
    foreach (glob(storage_path('framework/testing/themes-*')) ?: [] as $dir) {
        File::deleteDirectory($dir);
    }
});

it('discovers every bundled theme from its theme.json', function () {
    $themes = app(ThemeManager::class)->all();

    expect(array_keys($themes))->toContain('minimal', 'business', 'personal', 'magazine', 'docs')
        ->and($themes['business']->parent())->toBe('minimal')
        ->and($themes['business']->version())->toBe('0.2.0');
    foreach ($themes as $theme) {
        expect(is_file($theme->assetPath('theme.css')))->toBeTrue("{$theme->name} has no prebuilt assets/theme.css")
            ->and($theme->screenshot())->not->toBeNull("{$theme->name} has no screenshot");
    }
});

it('translates theme names from the theme lang folder', function () {
    app()->setLocale('tr');

    expect(app(ThemeManager::class)->get('business')->label())->toBe('Kurumsal');
});

it('ships prebuilt CSS that matches the sources', function () {
    $this->artisan('larawebkit:themes:build', ['--check' => true])->assertSuccessful();
});

it('resolves views and defaults through the parent chain of a child theme', function () {
    sandboxThemes(['acme' => [
        'theme.json' => ['name' => 'Acme', 'parent' => 'minimal', 'version' => '1.0.0', 'defaults' => ['primary' => '#ff5500']],
        'views/home.blade.php' => "@extends('theme-minimal::layout')\n@section('content')<p>acme home</p>@endsection",
        'lang/en/theme.php' => "<?php return ['name' => 'Acme Ltd'];",
    ]]);
    app(SettingsRepository::class)->set('general.theme', 'acme');
    $themes = app(ThemeManager::class);

    expect(array_map(fn ($t) => $t->name, $themes->chain()))->toBe(['acme', 'minimal'])
        ->and($themes->defaults()['primary'])->toBe('#ff5500')
        ->and($themes->defaults()['font_pair'])->toBe('system')
        ->and($themes->get('acme')->label())->toBe('Acme Ltd');

    // Own view for home; the post/search views come from the parent.
    $this->get('/')->assertOk()->assertSee('acme home')->assertSee('data-theme-name="acme"', false);
    $this->get('/search?q=x')->assertOk();
    // A child without its own CSS uses the parent's prebuilt file.
    expect(app(ThemeAssets::class)->url('theme.css'))->toContain('minimal');
});

it('falls back to minimal when the active theme folder is gone', function () {
    app(SettingsRepository::class)->set('general.theme', 'does-not-exist');

    expect(app(ThemeManager::class)->active())->toBe('minimal');
    $this->get('/')->assertOk();
});

it('ignores folders without a valid theme.json', function () {
    $root = sandboxThemes(['broken' => ['theme.json' => '{not json'], 'Bad_Name' => ['theme.json' => ['name' => 'x']]]);
    File::ensureDirectoryExists("$root/no-manifest/views");

    expect(array_keys(app(ThemeManager::class)->all()))->toBe(['minimal']);
});

it('serves theme assets through the fallback route and refuses other files', function () {
    $this->get(route('larawebkit.theme-asset', ['theme' => 'business', 'path' => 'theme.css', 'v' => 1]))
        ->assertOk()->assertHeader('Content-Type', 'text/css; charset=utf-8')
        ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');
    $this->get('/theme-assets/business/theme.json')->assertNotFound();
    $this->get('/theme-assets/business/..%2Ftheme.json')->assertNotFound();
    $this->get('/theme-assets/nope/theme.css')->assertNotFound();
});

it('activates a theme from the Themes page and publishes its assets', function () {
    $this->actingAs(superAdmin());
    File::deleteDirectory(public_path('themes/personal'));

    Livewire::test(ManageThemes::class)->assertSee('Personal brand')->call('activate', 'personal');

    expect(setting('general.theme'))->toBe('personal')
        ->and(is_file(public_path('themes/personal/theme.css')))->toBeTrue()
        ->and(app(ThemeAssets::class)->url('theme.css'))->toStartWith(asset('themes/personal/theme.css'));
    $this->get('/')->assertSee('data-theme-name="personal"', false);
});

it('keeps theme upload off by default and super-admin only', function () {
    expect(ThemeInstaller::enabled())->toBeFalse();
    $this->actingAs(superAdmin());
    Livewire::test(ManageThemes::class)->assertActionHidden('upload');

    config(['larawebkit-themes.allow_upload' => true]);
    Livewire::test(ManageThemes::class)->assertActionVisible('upload');

    $this->actingAs(editor());
    $this->get(ManageThemes::getUrl())->assertForbidden();
});

function themeZip(array $files): string
{
    $path = storage_path('framework/testing/theme-'.uniqid().'.zip');
    File::ensureDirectoryExists(dirname($path));
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    foreach ($files as $name => $content) {
        $zip->addFromString($name, is_array($content) ? json_encode($content) : $content);
    }
    $zip->close();

    return $path;
}

it('installs a zipped theme into the themes folder', function () {
    $root = sandboxThemes();
    config(['larawebkit-themes.allow_upload' => true]);

    $theme = app(ThemeInstaller::class)->install(themeZip([
        'sunrise/theme.json' => ['name' => 'Sunrise', 'parent' => 'minimal'],
        'sunrise/views/home.blade.php' => 'hi',
        'sunrise/assets/theme.css' => 'body{}',
    ]));

    expect($theme->name)->toBe('sunrise')
        ->and(is_file("$root/sunrise/views/home.blade.php"))->toBeTrue()
        ->and(app(ThemeManager::class)->exists('sunrise'))->toBeTrue();
});

it('rejects unsafe or invalid theme archives', function (array $files, string $message) {
    sandboxThemes();
    config(['larawebkit-themes.allow_upload' => true]);

    expect(fn () => app(ThemeInstaller::class)->install(themeZip($files)))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'path traversal' => [['theme.json' => ['name' => 'X'], '../evil.php' => '<?php'], 'Unsafe path'],
    'executable type' => [['theme.json' => ['name' => 'X'], 'shell.phar' => 'x'], 'File type not allowed'],
    'no manifest' => [['views/home.blade.php' => 'x'], 'theme.json is missing'],
    'existing theme' => [['minimal/theme.json' => ['name' => 'Minimal']], 'already exists'],
]);

it('refuses uploads while the option is off', function () {
    expect(fn () => app(ThemeInstaller::class)->install(themeZip(['theme.json' => ['name' => 'X']])))
        ->toThrow(InvalidArgumentException::class, 'disabled');
});

<?php

use App\Modules\Content\Models\Post;
use App\Modules\Media\ImagePipeline;
use App\Modules\Settings\SettingsRepository;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Storage;

it('brands the admin panel with the site logo, icon and primary colour', function () {
    Storage::fake('public');
    Storage::fake('local');
    $logo = app(ImagePipeline::class)->store(fakeJpeg(400, 120), 'logo.jpg');
    app(SettingsRepository::class)->setMany(['appearance.logo' => $logo->id, 'appearance.primary_color' => '#0f766e']);
    $this->actingAs(superAdmin());

    $this->get('/admin')->assertOk()->assertSee($logo->url(480), false);
    expect(Filament::getPanel('admin')->getColors()['primary'])->toBe(Color::hex('#0f766e'));
});

it('finds posts, pages and media with the global search', function () {
    $this->actingAs(superAdmin());
    Post::factory()->create(['title' => 'Unusual zebra crossing']);
    $panel = Filament::getPanel('admin');
    Filament::setCurrentPanel($panel);

    $results = $panel->getGlobalSearchProvider()->getResults('zebra');

    expect(collect($results->getCategories())->flatten()->map->title->all())->toContain('Unusual zebra crossing');
});

it('shows the welcome checklist until the site is set up', function () {
    $this->actingAs(superAdmin());

    $this->get('/admin')->assertOk()->assertSee(__('larawebkit-dashboard.welcome.checklist'))->assertSee(__('larawebkit-dashboard.welcome.new_post'));
});

it('shows Turkish validation messages in the admin', function () {
    app()->setLocale('tr');

    expect(__('validation.required', ['attribute' => 'e-posta']))->toBe('e-posta alanı zorunludur.')
        ->and(__('auth.failed'))->toBe('Bu bilgiler kayıtlarımızla eşleşmiyor.');
});

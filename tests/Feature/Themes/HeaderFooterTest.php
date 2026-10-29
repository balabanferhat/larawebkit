<?php

use App\Modules\Content\Filament\Resources\Menus\Pages\CreateMenu;
use App\Modules\Content\Models\Menu;
use App\Modules\Content\Models\MenuItem;
use App\Modules\Content\Models\Page;
use App\Modules\Settings\SettingsRepository;
use App\Modules\Themes\Support\Icons;
use Livewire\Livewire;

beforeEach(fn () => app(SettingsRepository::class)->set('features.page_cache', false));

it('builds a two-level menu tree and hides unpublished targets', function () {
    $published = Page::factory()->create(['slug' => 'services', 'status' => 'published', 'published_at' => now()->subDay()]);
    $draft = Page::factory()->create(['slug' => 'secret', 'status' => 'draft']);
    $menu = Menu::create(['name' => 'Main', 'location' => 'header']);
    $parent = $menu->items()->create(['label' => 'Services', 'linkable_type' => 'page', 'linkable_id' => $published->id, 'sort_order' => 1]);
    $parent->children()->create(['label' => 'Design', 'url' => '/services/design', 'sort_order' => 1]);
    $parent->children()->create(['label' => 'Hidden', 'linkable_type' => 'page', 'linkable_id' => $draft->id, 'sort_order' => 2]);
    $toggle = $menu->items()->create(['label' => 'Company', 'sort_order' => 2]);
    $toggle->children()->create(['label' => 'About', 'url' => '/about']);
    $menu->items()->create(['label' => 'Empty parent', 'sort_order' => 3]);

    $tree = Menu::for('header');

    expect($tree->pluck('label')->all())->toBe(['Services', 'Company'])
        ->and($tree[0]['children'])->toHaveCount(1)
        ->and($tree[1]['url'])->toBeNull()
        ->and(MenuItem::where('label', 'Design')->value('menu_id'))->toBe($menu->id);
});

it('renders accessible dropdowns, the mobile menu toggle and the CTA', function () {
    $menu = Menu::create(['name' => 'Main', 'location' => 'header']);
    $parent = $menu->items()->create(['label' => 'Products', 'url' => '/products']);
    $parent->children()->create(['label' => 'Widgets', 'url' => '/products/widgets']);
    app(SettingsRepository::class)->setMany(['header.cta_label' => 'Get a quote', 'header.cta_url' => '/contact']);

    $this->get('/')->assertOk()
        ->assertSee('aria-controls="lwk-header-panel"', false)
        ->assertSee('class="lwk-sub-toggle "', false)
        ->assertSee('aria-expanded="false" aria-controls="lwk-sub-header-0"', false)
        ->assertSee('Show the Products submenu')
        ->assertSee('href="/products/widgets"', false)
        ->assertSee('class="lwk-button lwk-header-cta">Get a quote', false);
});

it('renders footer columns, contact details, social icons and the legal row', function () {
    Menu::create(['name' => 'Company', 'location' => 'footer'])->items()->create(['label' => 'About us', 'url' => '/about']);
    Menu::create(['name' => 'Resources', 'location' => 'footer_secondary'])->items()->create(['label' => 'Guides', 'url' => '/guides']);
    Menu::create(['name' => 'Legal', 'location' => 'legal'])->items()->create(['label' => 'Imprint', 'url' => '/imprint']);
    app(SettingsRepository::class)->setMany([
        'general.contact_email' => 'hello@example.com',
        'organization.phone' => '+90 212 000 00 00',
        'footer.text' => 'We build things.',
        'social.links' => ['Instagram' => 'https://instagram.com/example', 'LinkedIn' => 'https://www.linkedin.com/company/example'],
    ]);

    $this->get('/')->assertOk()
        ->assertSeeInOrder(['Company', 'About us', 'Resources', 'Guides', 'Contact', 'hello@example.com'])
        ->assertSee('href="tel:+902120000000"', false)
        ->assertSee('We build things.')
        ->assertSee('aria-label="Instagram profile"', false)
        ->assertSee('Imprint');
});

it('shows a dismissible announcement bar', function () {
    app(SettingsRepository::class)->setMany(['header.announcement' => 'Spring sale: 20% off', 'header.announcement_url' => '/sale']);

    $this->get('/')->assertSee('Spring sale: 20% off')->assertSee('data-dismiss-announcement', false)->assertSee('href="/sale"', false);
});

it('maps social networks to icons, by name or by URL', function () {
    expect(Icons::socialKey('Twitter'))->toBe('x')
        ->and(Icons::socialKey('My shop', 'https://www.instagram.com/shop'))->toBe('instagram')
        ->and(Icons::socialKey('Personal site', 'https://example.com'))->toBeNull()
        ->and((string) Icons::social('Personal site', 'https://example.com'))->toContain('<svg');
});

it('saves nested menu items from the admin', function () {
    $this->actingAs(superAdmin());

    Livewire::test(CreateMenu::class)
        ->fillForm([
            'name' => 'Main',
            'location' => 'header',
            'rootItems' => [
                ['label' => 'Services', 'url' => '/services', 'children' => [['label' => 'Design', 'url' => '/design']]],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $design = MenuItem::where('label', 'Design')->first();
    expect($design->parent->label)->toBe('Services')
        ->and($design->menu_id)->toBe(Menu::where('location', 'header')->value('id'));
});

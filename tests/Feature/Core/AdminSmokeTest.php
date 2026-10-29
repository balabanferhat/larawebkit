<?php

use App\Modules\Content\Filament\Resources\Pages\PageResource;
use App\Modules\Content\Filament\Resources\Posts\PostResource;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Events\Filament\Resources\Events\EventResource;
use App\Modules\Events\Models\Event;
use App\Modules\Logs\Filament\Resources\AuthLogs\AuthLogResource;
use App\Modules\Settings\SettingsRepository;
use Filament\Facades\Filament;

it('renders every admin page for a super admin', function () {
    $this->actingAs(superAdmin());
    app(SettingsRepository::class)->setMany(['features.newsletter' => true, 'features.events' => true]);
    $event = Event::create(['title' => 'Meetup', 'starts_at' => now()->addDay()]);
    $post = Post::factory()->create();
    $page = Page::factory()->create();
    $panel = Filament::getPanel('admin');

    $this->get('/admin')->assertOk();
    foreach ($panel->getResources() as $resource) {
        $this->get($resource::getUrl('index'))->assertOk();
    }
    foreach ($panel->getPages() as $page_) {
        $this->get($page_::getUrl())->assertOk();
    }
    $this->get(PostResource::getUrl('edit', ['record' => $post]))->assertOk();
    $this->get(PageResource::getUrl('edit', ['record' => $page]))->assertOk();
    $this->get(EventResource::getUrl('edit', ['record' => $event]))->assertOk();
    $this->get(EventResource::getUrl('create'))->assertOk();
});

it('renders the admin in Turkish', function () {
    $this->actingAs(superAdmin(['locale' => 'tr']));
    app()->setLocale('tr');

    $this->get(PostResource::getUrl('create'))->assertOk()->assertSee('Odak anahtar kelimeler');
});

it('lets an author see the dashboard but not the logs', function () {
    $this->actingAs(author());

    $this->get('/admin')->assertOk()->assertDontSee(__('larawebkit-dashboard.recent_audit'));
    $this->get(AuthLogResource::getUrl())->assertForbidden();
});

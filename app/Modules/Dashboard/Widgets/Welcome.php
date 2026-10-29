<?php

namespace App\Modules\Dashboard\Widgets;

use App\Modules\Content\Filament\Resources\Pages\PageResource;
use App\Modules\Content\Filament\Resources\Posts\PostResource;
use App\Modules\Content\Models\Post;
use App\Modules\Core\ModuleRegistry;
use App\Modules\Media\Filament\Resources\Media\MediaResource;
use App\Modules\Settings\Filament\Pages\ManageSettings;
use App\Modules\Themes\Filament\Pages\ManageAppearance;
use App\Modules\Themes\Filament\Pages\ManageThemes;
use Filament\Widgets\Widget;

/** Greeting, quick actions and a short "finish setting up your site" checklist. */
class Welcome extends Widget
{
    protected static ?int $sort = 0;

    /** Rendered with the page (no lazy placeholder): it is the first thing people see. */
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'admin.widgets.welcome';

    public static function canView(): bool
    {
        return auth()->user()?->can('view dashboard') ?? false;
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $hour = (int) now()->format('G');
        $actions = array_values(array_filter([
            $user?->can('manage posts') || $user?->can('manage own posts') ? ['label' => __('larawebkit-dashboard.welcome.new_post'), 'url' => PostResource::getUrl('create'), 'icon' => 'heroicon-o-pencil-square'] : null,
            $user?->can('manage pages') ? ['label' => __('larawebkit-dashboard.welcome.new_page'), 'url' => PageResource::getUrl('create'), 'icon' => 'heroicon-o-document-plus'] : null,
            ModuleRegistry::enabled('media') && MediaResource::canViewAny() ? ['label' => __('larawebkit-dashboard.welcome.media'), 'url' => MediaResource::getUrl(), 'icon' => 'heroicon-o-photo'] : null,
            ModuleRegistry::enabled('themes') && ManageAppearance::canAccess() ? ['label' => __('larawebkit-dashboard.welcome.appearance'), 'url' => ManageAppearance::getUrl(), 'icon' => 'heroicon-o-swatch'] : null,
        ]));

        $checklist = [];
        if ($user?->can('manage settings')) {
            $checklist = [
                ['done' => setting('general.theme', 'minimal') !== 'minimal', 'label' => __('larawebkit-dashboard.welcome.check_theme'), 'url' => ModuleRegistry::enabled('themes') ? ManageThemes::getUrl() : null],
                ['done' => filled(setting('appearance.logo')) && filled(setting('appearance.favicon')), 'label' => __('larawebkit-dashboard.welcome.check_brand'), 'url' => ModuleRegistry::enabled('themes') ? ManageAppearance::getUrl() : null],
                ['done' => filled(setting('general.tagline')) && filled(setting('general.contact_email')), 'label' => __('larawebkit-dashboard.welcome.check_basics'), 'url' => ManageSettings::getUrl()],
                ['done' => Post::published()->exists(), 'label' => __('larawebkit-dashboard.welcome.check_post'), 'url' => PostResource::getUrl('create')],
            ];
        }

        return [
            'greeting' => __('larawebkit-dashboard.welcome.'.($hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening')), ['name' => strtok((string) $user?->name, ' ')]),
            'actions' => $actions,
            'checklist' => collect($checklist)->every('done') ? [] : $checklist,
        ];
    }
}

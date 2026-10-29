<?php

namespace App\Modules\Themes\Filament\Pages;

use App\Modules\Settings\Filament\Pages\ManageSettings;
use App\Modules\Themes\Design\Favicons;
use BackedEnum;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Appearance: theme, logo, favicon, brand colours, fonts, radius, colour scheme,
 * header and footer options. Same typed settings as the Settings page
 * (definitions with 'page' => 'appearance').
 */
class ManageAppearance extends ManageSettings
{
    protected static ?string $settingsPage = 'appearance';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'appearance';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.appearance');
    }

    public static function getNavigationLabel(): string
    {
        return __('larawebkit-themes.appearance.title');
    }

    public function getTitle(): string
    {
        return __('larawebkit-themes.appearance.title');
    }

    public function getSubheading(): ?string
    {
        return __('larawebkit-themes.appearance.subheading');
    }

    public function form(Schema $schema): Schema
    {
        $schema = parent::form($schema);

        return $schema->components([
            ...$schema->getComponents(),
            Section::make(__('larawebkit-themes.appearance.preview'))
                ->description(__('larawebkit-themes.appearance.preview_help'))
                ->collapsible()
                ->schema([ViewField::make('appearance_preview')->view('admin.appearance-preview')->dehydrated(false)]),
        ]);
    }

    protected function saved(): void
    {
        // New favicon or a changed image: rebuild the icon set now rather than on the next visit.
        app(Favicons::class)->forget();
        app(Favicons::class)->urls();
    }
}

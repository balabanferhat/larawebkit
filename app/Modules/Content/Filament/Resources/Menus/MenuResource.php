<?php

namespace App\Modules\Content\Filament\Resources\Menus;

use App\Modules\Content\Filament\Resources\Menus\Pages\CreateMenu;
use App\Modules\Content\Filament\Resources\Menus\Pages\EditMenu;
use App\Modules\Content\Filament\Resources\Menus\Pages\ListMenus;
use App\Modules\Content\Models\Menu;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?int $navigationSort = 40;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.content');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-content.menus.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-content.menus.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage menus') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('larawebkit-content.taxonomy.name'))->required()->maxLength(100),
            Select::make('location')->label(__('larawebkit-content.menus.location'))->required()->unique(ignoreRecord: true)->native(false)
                ->options(collect(Menu::LOCATIONS)->mapWithKeys(fn ($l) => [$l => __("larawebkit-content.menus.locations.$l")])->all()),
            Repeater::make('items')->label(__('larawebkit-content.menus.items'))->relationship()->orderColumn('sort_order')
                ->schema([
                    TextInput::make('label')->label(__('larawebkit-content.menus.label'))->required()->maxLength(100),
                    MorphToSelect::make('linkable')->label(__('larawebkit-content.menus.link_to'))
                        ->types([
                            MorphToSelect\Type::make(Page::class)->titleAttribute('title')->label(__('larawebkit-content.pages.singular')),
                            MorphToSelect\Type::make(Post::class)->titleAttribute('title')->label(__('larawebkit-content.posts.singular')),
                        ])->searchable()->preload(),
                    TextInput::make('url')->label(__('larawebkit-content.menus.custom_url'))->helperText(__('larawebkit-content.menus.custom_url_help'))->maxLength(500),
                    Toggle::make('new_tab')->label(__('larawebkit-content.menus.new_tab')),
                ])
                ->columns(2)->collapsible()->itemLabel(fn (array $state) => $state['label'] ?? null)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('larawebkit-content.taxonomy.name')),
                TextColumn::make('location')->label(__('larawebkit-content.menus.location'))->badge()->formatStateUsing(fn ($state) => __("larawebkit-content.menus.locations.$state")),
                TextColumn::make('items_count')->label(__('larawebkit-content.menus.items'))->counts('items'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'create' => CreateMenu::route('/create'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }
}

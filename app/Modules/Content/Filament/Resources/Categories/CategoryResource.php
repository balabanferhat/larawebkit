<?php

namespace App\Modules\Content\Filament\Resources\Categories;

use App\Modules\Content\Filament\Resources\Categories\Pages\ManageCategories;
use App\Modules\Content\Models\Category;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?int $navigationSort = 11;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.content');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-content.categories.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-content.categories.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage taxonomies') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('larawebkit-content.taxonomy.name'))->required()->maxLength(120),
            TextInput::make('slug')->label(__('larawebkit-editor.fields.slug'))->alphaDash()->unique(ignoreRecord: true)->maxLength(120),
            Textarea::make('description')->label(__('larawebkit-content.taxonomy.description'))->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('larawebkit-content.taxonomy.name'))->searchable()->sortable(),
                TextColumn::make('slug')->label(__('larawebkit-editor.fields.slug')),
                TextColumn::make('posts_count')->label(__('larawebkit-content.posts.plural'))->counts('posts')->sortable(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCategories::route('/')];
    }
}

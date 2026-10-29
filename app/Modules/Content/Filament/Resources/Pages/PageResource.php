<?php

namespace App\Modules\Content\Filament\Resources\Pages;

use App\Modules\Content\Filament\ContentTable;
use App\Modules\Content\Filament\RelationManagers\RevisionsRelationManager;
use App\Modules\Content\Filament\Resources\Pages\Pages\CreatePage;
use App\Modules\Content\Filament\Resources\Pages\Pages\EditPage;
use App\Modules\Content\Filament\Resources\Pages\Pages\ListPages;
use App\Modules\Content\Models\Page;
use App\Modules\Editor\Filament\EditorSchema;
use App\Modules\Logs\Filament\RelationManagers\AuditLogsRelationManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.content');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-content.pages.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-content.pages.plural');
    }

    public static function fullAccessPermission(): string
    {
        return 'manage pages';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage pages') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('manage pages') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    /** Global search (Ctrl/Cmd + K) over titles, slugs and the text of the content. */
    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'slug', 'content'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            __('larawebkit-editor.fields.status') => __('larawebkit-content.status.'.$record->status),
            __('larawebkit-editor.fields.locale') => strtoupper((string) $record->locale),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(EditorSchema::components('page'));
    }

    public static function table(Table $table): Table
    {
        return ContentTable::configure($table,
            [TextColumn::make('template')->label(__('larawebkit-editor.fields.template'))->formatStateUsing(fn (string $state) => __("larawebkit-content.templates.$state"))->toggleable()],
            [SelectFilter::make('template')->label(__('larawebkit-editor.fields.template'))->options(collect(Page::TEMPLATES)->mapWithKeys(fn ($t) => [$t => __("larawebkit-content.templates.$t")])->all())],
        )->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [RevisionsRelationManager::class, AuditLogsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}

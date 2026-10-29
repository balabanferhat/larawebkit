<?php

namespace App\Modules\Content\Filament\Resources\Posts;

use App\Modules\Content\Filament\ContentTable;
use App\Modules\Content\Filament\RelationManagers\RevisionsRelationManager;
use App\Modules\Content\Filament\Resources\Posts\Pages\CreatePost;
use App\Modules\Content\Filament\Resources\Posts\Pages\EditPost;
use App\Modules\Content\Filament\Resources\Posts\Pages\ListPosts;
use App\Modules\Content\Models\Post;
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

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.content');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-content.posts.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-content.posts.plural');
    }

    /** Users with this permission see every post; others only their own. */
    public static function fullAccessPermission(): string
    {
        return 'manage posts';
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->canAny(['manage posts', 'manage own posts']) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return $user?->can('manage posts') || ($user?->can('manage own posts') && $record->author_id === $user->getKey());
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

        return auth()->user()?->can('manage posts') ? $query : $query->where('author_id', auth()->id());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components(EditorSchema::components('post'));
    }

    public static function table(Table $table): Table
    {
        return ContentTable::configure($table,
            [TextColumn::make('categories.name')->label(__('larawebkit-content.categories.plural'))->badge()->toggleable()],
            [SelectFilter::make('categories')->label(__('larawebkit-content.categories.plural'))->relationship('categories', 'name')],
            static::class,
        );
    }

    public static function getRelations(): array
    {
        return [RevisionsRelationManager::class, AuditLogsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}

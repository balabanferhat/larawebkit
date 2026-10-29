<?php

namespace App\Modules\Media\Filament\Resources\Media;

use App\Modules\Logs\Filament\RelationManagers\AuditLogsRelationManager;
use App\Modules\Media\Filament\Resources\Media\Pages\CreateMedia;
use App\Modules\Media\Filament\Resources\Media\Pages\EditMedia;
use App\Modules\Media\Filament\Resources\Media\Pages\ListMedia;
use App\Modules\Media\Http\Controllers\MediaApiController;
use App\Modules\Media\Models\Media;
use App\Modules\Media\Support\ResponsiveImage;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use UnitEnum;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'title';

    /** @return list<string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'file_name', 'alt', 'caption'];
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.content');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-media.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-media.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage media') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('delete media') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make()->columnSpan(2)->schema([
                TextInput::make('title')->label(__('larawebkit-media.fields.title'))->maxLength(255),
                TextInput::make('alt')->label(__('larawebkit-media.fields.alt'))->required()->maxLength(500)->helperText(__('larawebkit-media.alt_help')),
                Textarea::make('caption')->label(__('larawebkit-media.fields.caption'))->rows(3),
            ]),
            Section::make()->columnSpan(1)->schema([
                TextEntry::make('preview')->hiddenLabel()->state(fn (?Media $record) => $record ? ResponsiveImage::tag($record, sizes: '320px', size: 640, attributes: ['style' => 'max-width:100%;height:auto;border-radius:.5rem']) : null),
                TextEntry::make('file_name')->label(__('larawebkit-media.fields.file_name')),
                TextEntry::make('dimensions')->label(__('larawebkit-media.fields.dimensions'))->state(fn (?Media $record) => $record ? "{$record->width} × {$record->height} px" : null),
                TextEntry::make('size')->label(__('larawebkit-media.fields.size'))->state(fn (?Media $record) => $record ? MediaApiController::humanSize((int) $record->size).' · '.strtoupper((string) $record->format) : null),
                TextEntry::make('used')->label(__('larawebkit-media.fields.used_in'))->state(fn (?Media $record) => $record ? trans_choice('larawebkit-media.used_in', $record->usageCount(), ['count' => $record->usageCount()]) : null),
                TextEntry::make('attribution')->label(__('larawebkit-media.fields.attribution'))->state(fn (?Media $record) => $record ? ResponsiveImage::attribution($record) ?? new HtmlString('—') : null),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->contentGrid(['md' => 3, 'xl' => 5])
            ->columns([
                Stack::make([
                    ImageColumn::make('thumb')->state(fn (Media $record) => $record->url('thumb'))->imageHeight(160)->extraImgAttributes(['loading' => 'lazy', 'style' => 'width:100%;object-fit:cover;border-radius:.5rem']),
                    TextColumn::make('file_name')->label(__('larawebkit-media.fields.file_name'))->searchable(['file_name', 'title', 'alt'])->limit(28)->weight('medium'),
                    TextColumn::make('alt')->label(__('larawebkit-media.fields.alt'))->limit(40)->color('gray')->placeholder(__('larawebkit-media.missing_alt')),
                    TextColumn::make('usages_count')->counts('usages')->formatStateUsing(fn ($state) => trans_choice('larawebkit-media.used_in', (int) $state, ['count' => (int) $state]))->color('gray')->size('xs'),
                ]),
            ])
            ->filters([
                SelectFilter::make('source')->label(__('larawebkit-media.fields.source'))->options(['upload' => __('larawebkit-media.sources.upload'), 'pexels' => 'Pexels', 'import' => __('larawebkit-media.sources.import')]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->modalDescription(fn (Media $record) => $record->usageCount() > 0
                    ? __('larawebkit-media.in_use_warning', ['count' => $record->usageCount()])
                    : __('larawebkit-media.delete_confirm')),
            ]);
    }

    public static function getRelations(): array
    {
        return [AuditLogsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
            'create' => CreateMedia::route('/create'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }
}

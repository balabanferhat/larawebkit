<?php

namespace App\Modules\Content\Filament\Resources\Redirects;

use App\Modules\Content\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Modules\Content\Models\Redirect;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-content.redirects.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-content.redirects.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage redirects') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('from_path')->label(__('larawebkit-content.redirects.from'))->required()->prefix(url('/'))->maxLength(500)
                ->unique(ignoreRecord: true)->helperText(__('larawebkit-content.redirects.from_help'))
                ->dehydrateStateUsing(fn (?string $state) => Redirect::normalize($state)),
            Select::make('status_code')->label(__('larawebkit-content.redirects.type'))->native(false)->required()->default(301)->live()
                ->options([301 => __('larawebkit-content.redirects.codes.301'), 302 => __('larawebkit-content.redirects.codes.302'), 410 => __('larawebkit-content.redirects.codes.410')]),
            TextInput::make('to_url')->label(__('larawebkit-content.redirects.to'))->maxLength(500)
                ->helperText(__('larawebkit-content.redirects.to_help'))
                ->required(fn (Get $get) => (int) $get('status_code') !== 410)
                ->visible(fn (Get $get) => (int) $get('status_code') !== 410)
                ->rule(fn () => function (string $attribute, $value, $fail) {
                    if (filled($value) && ! str_starts_with($value, '/') && ! filter_var($value, FILTER_VALIDATE_URL)) {
                        $fail(__('larawebkit-content.redirects.invalid_target'));
                    }
                }),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('from_path')->label(__('larawebkit-content.redirects.from'))->searchable(),
                TextColumn::make('to_url')->label(__('larawebkit-content.redirects.to'))->searchable()->placeholder('—'),
                TextColumn::make('status_code')->label(__('larawebkit-content.redirects.type'))->badge(),
                IconColumn::make('is_automatic')->label(__('larawebkit-content.redirects.automatic'))->boolean(),
                TextColumn::make('hits')->label(__('larawebkit-content.redirects.hits'))->sortable(),
                TextColumn::make('last_hit_at')->label(__('larawebkit-content.redirects.last_hit'))->since()->placeholder('—'),
            ])
            ->filters([SelectFilter::make('status_code')->options([301 => '301', 302 => '302', 410 => '410'])])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRedirects::route('/')];
    }
}

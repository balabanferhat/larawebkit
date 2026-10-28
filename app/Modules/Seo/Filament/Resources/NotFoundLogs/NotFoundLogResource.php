<?php

namespace App\Modules\Seo\Filament\Resources\NotFoundLogs;

use App\Modules\Content\Filament\Resources\Redirects\RedirectResource;
use App\Modules\Content\Models\Redirect;
use App\Modules\Seo\Filament\Resources\NotFoundLogs\Pages\ListNotFoundLogs;
use App\Modules\Seo\Models\NotFoundLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** 404 log with a one-click "create redirect". */
class NotFoundLogResource extends Resource
{
    protected static ?string $model = NotFoundLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?int $navigationSort = 51;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.marketing');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-seo.not_found.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-seo.not_found.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage redirects') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('hits', 'desc')
            ->columns([
                TextColumn::make('path')->label(__('larawebkit-logs.fields.path'))->searchable()->limit(70),
                TextColumn::make('hits')->label(__('larawebkit-content.redirects.hits'))->sortable(),
                TextColumn::make('last_referrer')->label(__('larawebkit-logs.fields.referrer'))->limit(40)->placeholder('—'),
                TextColumn::make('last_seen_at')->label(__('larawebkit-logs.ai_report.last_seen'))->since()->sortable(),
            ])
            ->recordActions([
                Action::make('redirect')
                    ->label(__('larawebkit-seo.not_found.create_redirect'))
                    ->icon('heroicon-o-arrows-right-left')
                    ->visible(fn () => auth()->user()?->can('manage redirects'))
                    ->schema([
                        TextInput::make('to_url')->label(__('larawebkit-content.redirects.to'))->required()->maxLength(500),
                        Select::make('status_code')->label(__('larawebkit-content.redirects.type'))->default(301)->required()
                            ->options([301 => __('larawebkit-content.redirects.codes.301'), 302 => __('larawebkit-content.redirects.codes.302'), 410 => __('larawebkit-content.redirects.codes.410')]),
                    ])
                    ->action(function (NotFoundLog $record, array $data) {
                        Redirect::updateOrCreate(['from_path' => $record->path], ['to_url' => $data['to_url'], 'status_code' => (int) $data['status_code']]);
                        $record->delete();
                        Notification::make()->success()->title(__('larawebkit-seo.not_found.redirect_created'))
                            ->actions([Action::make('open')->label(__('larawebkit-content.redirects.plural'))->url(RedirectResource::getUrl())])->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListNotFoundLogs::route('/')];
    }
}

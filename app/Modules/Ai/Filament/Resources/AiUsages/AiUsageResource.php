<?php

namespace App\Modules\Ai\Filament\Resources\AiUsages;

use App\Modules\Ai\Filament\Resources\AiUsages\Pages\ListAiUsages;
use App\Modules\Ai\Models\AiUsage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AiUsageResource extends Resource
{
    protected static ?string $model = AiUsage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = 84;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('larawebkit.nav.logs');
    }

    public static function getModelLabel(): string
    {
        return __('larawebkit-ai.usage.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('larawebkit-ai.usage.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view logs') ?? false;
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
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('larawebkit-logs.fields.time'))->dateTime()->sortable(),
                TextColumn::make('action')->label(__('larawebkit-ai.usage.action'))->badge()->formatStateUsing(fn ($state) => __("larawebkit-ai.actions.$state")),
                TextColumn::make('provider')->label(__('larawebkit-ai.usage.provider')),
                TextColumn::make('model')->label(__('larawebkit-ai.usage.model')),
                TextColumn::make('input_tokens')->label(__('larawebkit-ai.usage.input'))->numeric(),
                TextColumn::make('output_tokens')->label(__('larawebkit-ai.usage.output'))->numeric(),
                TextColumn::make('cost_usd')->label(__('larawebkit-ai.usage.cost'))->money('USD', divideBy: 1, decimalPlaces: 4)->summarize(Sum::make()->money('USD', decimalPlaces: 2)),
                IconColumn::make('succeeded')->label(__('larawebkit-ai.usage.ok'))->boolean(),
                TextColumn::make('user.name')->label(__('larawebkit-logs.fields.user'))->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('action')->label(__('larawebkit-ai.usage.action'))->options(collect(array_keys(config('larawebkit-ai.actions')))->mapWithKeys(fn ($a) => [$a => __("larawebkit-ai.actions.$a")])->all()),
                SelectFilter::make('provider')->label(__('larawebkit-ai.usage.provider'))->options(collect(array_keys(config('larawebkit-ai.providers')))->mapWithKeys(fn ($p) => [$p => $p])->all()),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAiUsages::route('/')];
    }
}

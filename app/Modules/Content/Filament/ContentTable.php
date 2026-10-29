<?php

namespace App\Modules\Content\Filament;

use App\Models\User;
use App\Modules\Content\Filament\Resources\Posts\PostResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Columns, filters and actions shared by the page and post tables. */
class ContentTable
{
    public static function score(string $name, string $label): TextColumn
    {
        return TextColumn::make($name)->label($label)->badge()->sortable()->placeholder('—')
            ->color(fn (?int $state) => $state === null ? 'gray' : ($state >= 70 ? 'success' : ($state >= 45 ? 'warning' : 'danger')));
    }

    /** @param  array<int, mixed>  $extraColumns */
    public static function configure(Table $table, array $extraColumns = [], array $extraFilters = [], ?string $resource = null): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->label(__('larawebkit-editor.fields.title'))->searchable()->sortable()->limit(60)
                    ->description(fn (Model $r) => $r->publicPath()),
                TextColumn::make('status')->label(__('larawebkit-editor.fields.status'))->badge()
                    ->state(fn (Model $r) => $r->effectiveStatus())
                    ->formatStateUsing(fn (string $state) => __("larawebkit-content.status.$state"))
                    ->color(fn (string $state) => match ($state) {
                        'published' => 'success', 'scheduled' => 'info', 'pending' => 'warning', default => 'gray',
                    }),
                ...$extraColumns,
                self::score('seo_score', 'SEO'),
                self::score('human_score', __('larawebkit-editor.panel.human')),
                self::score('geo_score', 'GEO'),
                TextColumn::make('author.name')->label(__('larawebkit-editor.fields.author'))->toggleable(),
                TextColumn::make('locale')->label(__('larawebkit-editor.fields.locale'))->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')->label(__('larawebkit-editor.fields.published_at'))->dateTime()->sortable()->toggleable(),
                TextColumn::make('updated_at')->label(__('larawebkit-content.updated'))->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('larawebkit-editor.fields.status'))->options([
                    'draft' => __('larawebkit-content.status.draft'),
                    'pending' => __('larawebkit-content.status.pending'),
                    'published' => __('larawebkit-content.status.published'),
                    'scheduled' => __('larawebkit-content.status.scheduled'),
                ])->query(fn (Builder $q, array $data) => match ($data['value'] ?? null) {
                    'scheduled' => $q->where('status', 'published')->where('published_at', '>', now()),
                    'published' => $q->published(),
                    null, '' => $q,
                    default => $q->where('status', $data['value']),
                }),
                SelectFilter::make('author_id')->label(__('larawebkit-editor.fields.author'))->options(fn () => User::orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('locale')->label(__('larawebkit-editor.fields.locale'))->options(config('larawebkit.locales')),
                ...$extraFilters,
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    ...($resource ? [ContentActions::duplicate($resource)] : []),
                    DeleteAction::make(), RestoreAction::make(), ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([BulkActionGroup::make([...ContentActions::bulk($resource === PostResource::class), DeleteBulkAction::make()])]);
    }
}

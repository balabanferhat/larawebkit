<?php

namespace App\Modules\Content\Filament;

use App\Modules\Content\Models\Category;
use App\Modules\Content\Models\Page;
use App\Modules\Content\Models\Post;
use App\Modules\Editor\ContentScorer;
use App\Modules\Editor\PublishGate;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;

/** Duplicate, share-a-preview, bulk publish / unpublish / set category and status tabs for pages and posts. */
class ContentActions
{
    public static function duplicate(string $resource): Action
    {
        return Action::make('duplicate')->label(__('larawebkit-content.duplicate'))->icon('heroicon-o-document-duplicate')->color('gray')
            ->visible(fn () => $resource::canCreate())
            ->action(function (Model $record, $livewire) use ($resource) {
                $copy = self::copy($record);
                Notification::make()->success()->title(__('larawebkit-content.duplicated'))->send();
                $livewire->redirect($resource::getUrl('edit', ['record' => $copy]));
            });
    }

    /** A draft copy: new slug and translation group, not published, no revisions or redirects. */
    public static function copy(Model $record): Model
    {
        /** @var Post|Page $record */
        $copy = $record->replicate(['slug', 'translation_group', 'published_at', 'announced_at', 'seo_score', 'human_score', 'geo_score', 'word_count', 'path']);
        $copy->title = trim($record->title.' '.__('larawebkit-content.copy_suffix'));
        $copy->status = 'draft';
        $copy->save();
        if ($record instanceof Post) {
            $copy->categories()->sync($record->categories()->pluck('categories.id'));
            $copy->tags()->sync($record->tags()->pluck('tags.id'));
        }

        return $copy;
    }

    public static function sharePreview(string $type): Action
    {
        $hours = (int) config('larawebkit.preview_link_hours', 72);

        return Action::make('sharePreview')->label(__('larawebkit-content.share_preview'))->icon('heroicon-o-link')->color('gray')
            ->modalSubmitAction(false)->modalCancelActionLabel(__('larawebkit.actions.close'))
            ->modalContent(fn (Model $record) => new HtmlString(
                '<p style="margin-bottom:.75rem">'.e(__('larawebkit-content.share_preview_help', ['hours' => $hours])).'</p>'
                .'<input type="text" readonly onclick="this.select()" class="fi-input" style="width:100%;padding:.5rem .75rem;border:1px solid rgb(209 213 219);border-radius:.5rem;font-family:monospace;font-size:.8rem" value="'
                .e(self::previewUrl($type, $record)).'">'
            ));
    }

    public static function previewUrl(string $type, Model $record): string
    {
        return URL::temporarySignedRoute('larawebkit.preview.shared', now()->addHours((int) config('larawebkit.preview_link_hours', 72)), ['type' => $type, 'id' => $record->getKey()]);
    }

    /** @return list<BulkAction> */
    public static function bulk(bool $posts): array
    {
        $actions = [
            BulkAction::make('publish')->label(__('larawebkit-content.bulk.publish'))->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn () => auth()->user()?->can('publish content'))
                ->requiresConfirmation()->deselectRecordsAfterCompletion()
                ->action(function (Collection $records) {
                    $gate = app(PublishGate::class);
                    $check = $gate->enabled() && ! $gate->canOverride(auth()->user());
                    $published = $skipped = 0;
                    foreach ($records as $record) {
                        if ($record->status === 'published') {
                            continue;
                        }
                        if ($check && $gate->failures(app(ContentScorer::class)->inputFromModel($record)) !== []) {
                            $skipped++;

                            continue;
                        }
                        $record->update(['status' => 'published', 'published_at' => $record->published_at ?? now()]);
                        $published++;
                    }
                    $note = Notification::make()->title(trans_choice('larawebkit-content.bulk.published', $published, ['count' => $published]));
                    ($skipped ? $note->warning()->body(__('larawebkit-content.bulk.gate_skipped', ['count' => $skipped])) : $note->success())->send();
                }),
            BulkAction::make('unpublish')->label(__('larawebkit-content.bulk.unpublish'))->icon('heroicon-o-eye-slash')->color('gray')
                ->visible(fn () => auth()->user()?->can('publish content'))
                ->requiresConfirmation()->deselectRecordsAfterCompletion()
                ->action(function (Collection $records) {
                    $count = 0;
                    foreach ($records as $record) {
                        if ($record->status !== 'draft') {
                            $record->update(['status' => 'draft']);
                            $count++;
                        }
                    }
                    Notification::make()->success()->title(trans_choice('larawebkit-content.bulk.unpublished', max(1, $count), ['count' => $count]))->send();
                }),
        ];
        if ($posts) {
            $actions[] = BulkAction::make('moveCategory')->label(__('larawebkit-content.bulk.move_category'))->icon('heroicon-o-folder')->color('gray')
                ->schema([
                    Select::make('category')->label(__('larawebkit-content.bulk.category'))->options(fn () => Category::orderBy('name')->pluck('name', 'id'))->required()->searchable(),
                    Toggle::make('replace')->label(__('larawebkit-content.bulk.replace'))->default(true),
                ])
                ->deselectRecordsAfterCompletion()
                ->action(function (Collection $records, array $data) {
                    foreach ($records as $post) {
                        $data['replace'] ? $post->categories()->sync([$data['category']]) : $post->categories()->syncWithoutDetaching([$data['category']]);
                        $post->touch();
                    }
                    Notification::make()->success()->title(trans_choice('larawebkit-content.bulk.moved', $records->count(), ['count' => $records->count()]))->send();
                });
        }

        return $actions;
    }

    /** @return array<string, Tab> status tabs for the list pages; "Scheduled" is sorted by publish date */
    public static function tabs(ListRecords $page): array
    {
        $count = function (callable $scope) use ($page): int {
            $query = $page::getResource()::getEloquentQuery();

            return $scope($query->whereNull($query->getModel()->getQualifiedDeletedAtColumn()))->count();
        };
        $scheduled = fn (Builder $q) => $q->where('status', 'published')->where('published_at', '>', now());

        return [
            'all' => Tab::make(__('larawebkit-content.tabs.all')),
            'published' => Tab::make(__('larawebkit-content.tabs.published'))->modifyQueryUsing(fn (Builder $query) => $query->published()),
            'scheduled' => Tab::make(__('larawebkit-content.tabs.scheduled'))->badge(fn () => $count($scheduled) ?: null)
                ->modifyQueryUsing(fn (Builder $query) => $scheduled($query)),
            'drafts' => Tab::make(__('larawebkit-content.tabs.drafts'))->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft')),
            'pending' => Tab::make(__('larawebkit-content.tabs.pending'))->badge(fn () => $count(fn ($q) => $q->where('status', 'pending')) ?: null)->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
        ];
    }
}

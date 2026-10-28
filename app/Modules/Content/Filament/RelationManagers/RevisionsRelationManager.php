<?php

namespace App\Modules\Content\Filament\RelationManagers;

use App\Modules\Content\Models\Revision;
use App\Modules\Content\Support\TextDiff;
use App\Modules\Editor\Scoring\TextAnalyzer;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/** "Revisions" tab: every save, a word diff against the current version, and restore. */
class RevisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'revisions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('larawebkit-content.revisions.title');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label(__('larawebkit-logs.fields.time'))->dateTime()->description(fn (Revision $r) => $r->created_at->diffForHumans()),
                TextColumn::make('user.name')->label(__('larawebkit-logs.fields.user'))->placeholder('—'),
                TextColumn::make('data.title')->label(__('larawebkit-editor.fields.title'))->limit(50),
                IconColumn::make('is_autosave')->label(__('larawebkit-content.revisions.autosave'))->boolean(),
            ])
            ->recordActions([
                Action::make('compare')
                    ->label(__('larawebkit-content.revisions.compare'))
                    ->icon('heroicon-o-arrows-right-left')
                    ->modalHeading(__('larawebkit-content.revisions.compare_heading'))
                    ->modalSubmitAction(false)
                    ->modalContent(fn (Revision $record) => new HtmlString(self::diff($record, $this->getOwnerRecord()))),
                Action::make('restore')
                    ->label(__('larawebkit-content.revisions.restore'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription(__('larawebkit-content.revisions.restore_help'))
                    ->action(function (Revision $record) {
                        $this->getOwnerRecord()->restoreRevision($record);
                        Notification::make()->success()->title(__('larawebkit-content.revisions.restored'))->send();
                        $this->redirect(request()->header('Referer') ?: url()->previous());
                    }),
            ]);
    }

    public static function diff(Revision $revision, Model $current): string
    {
        $html = '<div class="lwk-diff">';
        foreach ($revision->data as $field => $old) {
            $new = $current->getAttribute($field);
            $oldText = is_array($old) ? json_encode($old, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : ($field === 'content' ? TextAnalyzer::htmlToText((string) $old) : (string) $old);
            $newText = is_array($new) ? json_encode($new, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : ($field === 'content' ? TextAnalyzer::htmlToText((string) $new) : (string) $new);
            if ($oldText === $newText) {
                continue;
            }
            $label = e(__("larawebkit-editor.fields.$field"));
            $html .= "<h4>$label</h4><div class=\"lwk-diff-body\">".TextDiff::html($oldText, $newText).'</div>';
        }

        return $html === '<div class="lwk-diff">' ? '<p>'.e(__('larawebkit-content.revisions.identical')).'</p>' : $html.'</div>';
    }
}

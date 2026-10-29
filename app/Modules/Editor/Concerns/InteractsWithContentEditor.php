<?php

namespace App\Modules\Editor\Concerns;

use App\Modules\Editor\ContentScorer;
use App\Modules\Editor\PublishGate;
use App\Modules\Logs\AuditLogger;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * For the Create/Edit pages of pages and posts: publish gate, author
 * restrictions, translation linking, autosave and "restore autosave".
 */
trait InteractsWithContentEditor
{
    /** Called from the browser every N seconds while the form has unsaved changes. */
    public function autosave(): ?string
    {
        $record = $this->getRecord();
        if (! $record instanceof Model || ! method_exists($record, 'createRevision')) {
            return null;
        }

        $data = array_intersect_key($this->data ?? [], array_flip($record->revisionFields()));
        // Client state of the rich editor is TipTap JSON: store HTML like a normal save.
        if (isset($data['content']) && is_array($data['content'])) {
            $data['content'] = $this->richEditorHtml($data['content']);
        }
        $record->createRevision($data, autosave: true);

        return now()->format('H:i');
    }

    protected function restoreAutosaveAction(): Action
    {
        return Action::make('restoreAutosave')
            ->label(__('larawebkit-editor.autosave.restore'))
            ->color('warning')
            ->icon('heroicon-o-arrow-uturn-left')
            ->visible(function () {
                $autosave = $this->getRecord()?->latestAutosave();

                return $autosave && $autosave->created_at->gt($this->getRecord()->updated_at);
            })
            ->requiresConfirmation()
            ->modalDescription(fn () => __('larawebkit-editor.autosave.restore_help', ['time' => $this->getRecord()->latestAutosave()?->created_at?->diffForHumans()]))
            ->action(function () {
                $autosave = $this->getRecord()->latestAutosave();
                $this->form->fill([...$this->data, ...$autosave->data]);
                Notification::make()->success()->title(__('larawebkit-editor.autosave.restored'))->send();
            });
    }

    /** @param  array<string, mixed>  $data */
    protected function prepareContentData(array $data, ?Model $record): array
    {
        $user = auth()->user();

        // Authors cannot publish; they submit for review.
        if (($data['status'] ?? null) === 'published' && ! $user?->can('publish content')) {
            $data['status'] = $record?->status === 'published' ? 'published' : 'pending';
        }
        if (! $user?->can(static::getResource()::fullAccessPermission())) {
            $data['author_id'] = $record?->author_id ?? $user?->getKey();
        }

        $translationOf = $this->data['translation_of'] ?? null;
        if ($translationOf) {
            $other = static::getResource()::getModel()::find($translationOf);
            if ($other) {
                $other->translation_group ??= (string) Str::uuid();
                $other->saveQuietly();
                $data['translation_group'] = $other->translation_group;
            }
        }

        if (($data['status'] ?? null) !== 'published') {
            $data['gate_override_reason'] = null;
        }

        return $data;
    }

    protected function richEditorHtml(array $document): string
    {
        foreach ($this->form->getFlatComponents() as $component) {
            if ($component instanceof RichEditor && $component->getName() === 'content') {
                return $component->getTipTapEditor()->setContent($document)->getHTML();
            }
        }

        return '';
    }

    /**
     * Stops the save (with a notification) when the publish gate fails.
     *
     * @param  array<string, mixed>  $state  dehydrated form data (content as HTML)
     */
    protected function enforcePublishGate(array $state, ?Model $record): void
    {
        $gate = app(PublishGate::class);
        $becomesPublished = ($state['status'] ?? null) === 'published' && $record?->status !== 'published';

        if (! $becomesPublished || ! $gate->enabled() || ! auth()->user()?->can('publish content')) {
            return;
        }

        $failures = $gate->failures(app(ContentScorer::class)->inputFromState($state));
        if ($failures === []) {
            return;
        }

        $reason = trim((string) ($state['gate_override_reason'] ?? ''));
        if ($gate->canOverride(auth()->user()) && $reason !== '') {
            $this->gateOverride = ['reason' => $reason, 'failures' => $failures];

            return;
        }

        Notification::make()
            ->danger()
            ->title(__('larawebkit-editor.gate.blocked'))
            ->body(implode("\n", $failures).($gate->canOverride(auth()->user()) ? "\n\n".__('larawebkit-editor.gate.override_hint') : ''))
            ->persistent()
            ->send();

        $this->halt();
    }

    /** @var array{reason: string, failures: list<string>}|null */
    public ?array $gateOverride = null;

    protected function logGateOverride(Model $record): void
    {
        if ($this->gateOverride) {
            AuditLogger::record('gate_override', $record, [], ['failures' => $this->gateOverride['failures']], $this->gateOverride['reason']);
            $this->gateOverride = null;
        }
    }
}

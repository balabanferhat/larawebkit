<?php

namespace App\Modules\Content\Filament\Resources\Posts\Pages;

use App\Modules\Content\Filament\ContentActions;
use App\Modules\Content\Filament\Resources\Posts\PostResource;
use App\Modules\Editor\Concerns\InteractsWithContentEditor;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    use InteractsWithContentEditor;

    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->restoreAutosaveAction(),
            Action::make('view')
                ->label(__('larawebkit-content.view'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => $this->getRecord()->isPublished() ? $this->getRecord()->url() : route('larawebkit.preview', ['type' => 'post', 'id' => $this->getRecord()->getKey()]))
                ->openUrlInNewTab(),
            ContentActions::sharePreview('post')->visible(fn () => ! $this->getRecord()->isPublished()),
            ContentActions::duplicate(PostResource::class),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->enforcePublishGate($data, $this->getRecord());

        return $this->prepareContentData($data, $this->getRecord());
    }

    protected function afterSave(): void
    {
        $this->logGateOverride($this->getRecord());
    }
}

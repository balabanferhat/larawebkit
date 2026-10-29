<?php

namespace App\Modules\Media\Filament\Resources\Media\Pages;

use App\Modules\Media\Filament\Resources\Media\MediaResource;
use App\Modules\Media\ImagePipeline;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Multi-file upload: every file goes through the WebP pipeline.
 */
class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('files')
                ->label(__('larawebkit-media.upload.label'))
                ->multiple()
                ->image()
                ->acceptedFileTypes((array) config('larawebkit-media.accepted_mime_types'))
                ->maxSize((int) config('larawebkit-media.max_upload_kb'))
                ->storeFiles(false)
                ->required(),
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $pipeline = app(ImagePipeline::class);
        $created = null;
        $stored = 0;
        foreach ((array) $data['files'] as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }
            try {
                $created = $pipeline->storeUpload($file, ['uploaded_by' => auth()->id()]);
                $stored++;
            } catch (InvalidArgumentException $e) {
                // Too large / wrong type: tell the user, keep processing the other files.
                Notification::make()->danger()->title($file->getClientOriginalName())->body($e->getMessage())->persistent()->send();
            }
        }

        if ($created === null) {
            $this->halt();
        }

        Notification::make()->success()->title(trans_choice('larawebkit-media.upload.done', $stored))->send();

        return $created;
    }

    protected function getRedirectUrl(): string
    {
        return MediaResource::getUrl('index');
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }
}

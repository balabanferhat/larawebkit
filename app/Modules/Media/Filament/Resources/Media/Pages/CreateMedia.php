<?php

namespace App\Modules\Media\Filament\Resources\Media\Pages;

use App\Modules\Media\Filament\Resources\Media\MediaResource;
use App\Modules\Media\ImagePipeline;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
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
        foreach ((array) $data['files'] as $file) {
            if ($file instanceof TemporaryUploadedFile) {
                $created = $pipeline->storeUpload($file, ['uploaded_by' => auth()->id()]);
            }
        }

        Notification::make()->success()->title(trans_choice('larawebkit-media.upload.done', count((array) $data['files'])))->send();

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

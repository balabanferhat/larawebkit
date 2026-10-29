<?php

namespace App\Modules\Media\Filament;

use App\Modules\Media\ImagePipeline;
use App\Modules\Media\Models\Media;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * A media library select with thumbnails and an inline "upload new" form that
 * sends the file through the WebP pipeline. Stores the media id. Used by
 * settings, Appearance and the section blocks.
 */
class MediaPicker
{
    public static function make(string $name): Select
    {
        return Select::make($name)
            ->searchable()
            ->allowHtml()
            ->native(false)
            ->getSearchResultsUsing(fn (string $search) => self::labels(Media::query()->search($search)->latest()->limit(30)->get()))
            ->getOptionLabelUsing(fn ($value) => ($media = Media::find($value)) ? self::label($media) : null)
            ->options(fn () => self::labels(Media::query()->latest()->limit(30)->get()))
            ->createOptionForm([
                FileUpload::make('file')
                    ->label(__('larawebkit-media.upload.label'))
                    ->image()
                    ->acceptedFileTypes((array) config('larawebkit-media.accepted_mime_types'))
                    ->maxSize((int) config('larawebkit-media.max_upload_kb'))
                    ->storeFiles(false)
                    ->required(),
            ])
            ->createOptionUsing(function (array $data) {
                $file = $data['file'] ?? null;
                if (! $file instanceof TemporaryUploadedFile) {
                    throw new Halt;
                }
                try {
                    return app(ImagePipeline::class)->storeUpload($file, ['uploaded_by' => auth()->id()])->getKey();
                } catch (InvalidArgumentException $e) {
                    Notification::make()->danger()->title($file->getClientOriginalName())->body($e->getMessage())->persistent()->send();

                    throw new Halt;
                }
            });
    }

    /** @return array<int, string> */
    private static function labels($items): array
    {
        return $items->mapWithKeys(fn (Media $media) => [$media->getKey() => self::label($media)])->all();
    }

    private static function label(Media $media): string
    {
        $thumb = $media->url('thumb');

        return '<span style="display:flex;align-items:center;gap:.6rem">'
            .($thumb ? '<img src="'.e($thumb).'" alt="" width="36" height="36" style="width:36px;height:36px;object-fit:cover;border-radius:4px">' : '')
            .'<span>'.e($media->title ?: $media->file_name).'</span></span>';
    }
}

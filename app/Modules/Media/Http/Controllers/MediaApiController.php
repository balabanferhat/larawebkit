<?php

namespace App\Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Media\ImagePipeline;
use App\Modules\Media\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

/**
 * JSON endpoints behind the editor's media modal (library grid, upload,
 * details sidebar). Mounted under /{admin}/larawebkit/media, admin auth only.
 */
class MediaApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'manage media');

        $page = Media::query()
            ->search($request->string('search')->toString())
            ->withCount('usages')
            ->latest('id')
            ->paginate(min(60, max(10, (int) $request->integer('per_page', 30))));

        return response()->json([
            'data' => collect($page->items())->map(fn (Media $m) => self::present($m))->all(),
            'has_more' => $page->hasMorePages(),
            'next_page' => $page->hasMorePages() ? $page->currentPage() + 1 : null,
        ]);
    }

    public function show(Request $request, Media $media): JsonResponse
    {
        $this->authorizeAbility($request, 'manage media');

        return response()->json(['data' => self::present($media, true)]);
    }

    public function store(Request $request, ImagePipeline $pipeline): JsonResponse
    {
        $this->authorizeAbility($request, 'manage media');

        $request->validate([
            'file' => ['required', 'file', 'max:'.(int) config('larawebkit-media.max_upload_kb'), 'mimetypes:'.implode(',', (array) config('larawebkit-media.accepted_mime_types'))],
            'alt' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $media = $pipeline->storeUpload($request->file('file'), [
                'alt' => $request->input('alt'),
                'uploaded_by' => $request->user()->getKey(),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['file' => [$e->getMessage()]]], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => __('larawebkit-media.errors.processing')], 422);
        }

        return response()->json(['data' => self::present($media, true)], 201);
    }

    public function update(Request $request, Media $media): JsonResponse
    {
        $this->authorizeAbility($request, 'manage media');

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'alt' => ['nullable', 'string', 'max:500'],
            'caption' => ['nullable', 'string', 'max:2000'],
        ]);
        $media->update($data);

        return response()->json(['data' => self::present($media->fresh(), true)]);
    }

    public function destroy(Request $request, Media $media): JsonResponse
    {
        $this->authorizeAbility($request, 'delete media');

        $uses = $media->usageCount();
        if ($uses > 0 && ! $request->boolean('force')) {
            return response()->json(['message' => __('larawebkit-media.in_use_warning', ['count' => $uses]), 'in_use' => $uses], 409);
        }
        $media->delete();

        return response()->json(['deleted' => true]);
    }

    /** @return array<string, mixed> */
    public static function present(Media $media, bool $details = false): array
    {
        $data = [
            'id' => $media->id,
            'file_name' => $media->file_name,
            'title' => $media->title,
            'alt' => $media->alt,
            'thumb' => $media->url('thumb'),
            'width' => $media->width,
            'height' => $media->height,
            'source' => $media->source,
        ];

        if ($details) {
            $data += [
                'caption' => $media->caption,
                'url' => $media->url(),
                'size' => $media->size,
                'size_human' => self::humanSize((int) $media->size),
                'format' => $media->format,
                'created_at' => $media->created_at?->toIso8601String(),
                'created_human' => $media->created_at?->isoFormat('LL'),
                'used_in' => $media->usageCount(),
                'sizes' => collect(config('larawebkit-media.sizes'))->map(fn ($size, $name) => [
                    'name' => $name,
                    'url' => $media->url($size),
                    'width' => $media->variant($size)['width'] ?? null,
                    'height' => $media->variant($size)['height'] ?? null,
                ])->values()->all(),
                'photographer' => $media->photographer,
                'photographer_url' => $media->photographer_url,
                'source_url' => $media->source_url,
            ];
        }

        return $data;
    }

    public static function humanSize(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1).' MB' : max(1, (int) round($bytes / 1024)).' KB';
    }

    private function authorizeAbility(Request $request, string $ability): void
    {
        abort_unless($request->user()?->can($ability), 403);
    }
}

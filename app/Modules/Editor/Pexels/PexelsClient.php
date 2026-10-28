<?php

namespace App\Modules\Editor\Pexels;

use App\Modules\Media\ImagePipeline;
use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Optional Pexels integration (PEXELS_API_KEY). Without a key the feature is
 * hidden. Imported photos keep photographer attribution and the photo link,
 * as the Pexels guidelines require.
 */
class PexelsClient
{
    public const API = 'https://api.pexels.com/v1';

    public function enabled(): bool
    {
        return filled(config('services.pexels.key')) && (bool) setting('features.pexels', true);
    }

    /** @return array{photos: list<array<string, mixed>>, page: int, has_more: bool} */
    public function search(string $query, ?string $orientation = null, int $page = 1, int $perPage = 24): array
    {
        $this->guard();

        $response = Http::withHeaders(['Authorization' => (string) config('services.pexels.key')])
            ->timeout(15)
            ->get(self::API.'/search', array_filter([
                'query' => $query,
                'orientation' => in_array($orientation, ['landscape', 'portrait', 'square'], true) ? $orientation : null,
                'page' => max(1, $page),
                'per_page' => min(80, max(1, $perPage)),
            ]));

        if (! $response->successful()) {
            throw new RuntimeException(__('larawebkit-editor.pexels.error', ['status' => $response->status()]));
        }

        $photos = collect($response->json('photos', []))->map(fn (array $p) => [
            'id' => (string) $p['id'],
            'width' => $p['width'] ?? null,
            'height' => $p['height'] ?? null,
            'alt' => $p['alt'] ?? '',
            'url' => $p['url'] ?? null,
            'photographer' => $p['photographer'] ?? '',
            'photographer_url' => $p['photographer_url'] ?? null,
            'thumb' => $p['src']['medium'] ?? $p['src']['small'] ?? null,
        ])->all();

        return ['photos' => $photos, 'page' => $page, 'has_more' => filled($response->json('next_page'))];
    }

    /** Download a photo into the media library (WebP, stock widths). Reuses an earlier import of the same photo. */
    public function import(string $photoId, ?int $userId = null): Media
    {
        $this->guard();

        $existing = Media::where('source', 'pexels')->where('source_id', $photoId)->first();
        if ($existing) {
            return $existing;
        }

        $photo = Http::withHeaders(['Authorization' => (string) config('services.pexels.key')])
            ->timeout(15)->get(self::API.'/photos/'.rawurlencode($photoId));
        if (! $photo->successful()) {
            throw new RuntimeException(__('larawebkit-editor.pexels.error', ['status' => $photo->status()]));
        }
        $p = $photo->json();
        $source = $p['src']['original'] ?? $p['src']['large2x'] ?? null;
        if (! $source || ! str_starts_with($source, 'https://images.pexels.com/')) {
            throw new RuntimeException(__('larawebkit-editor.pexels.error', ['status' => 'src']));
        }

        // Ask the CDN for a 1600px version instead of the (huge) original.
        $download = Http::timeout(30)->get($source.(str_contains($source, '?') ? '&' : '?').'auto=compress&cs=tinysrgb&w=1600');
        if (! $download->successful()) {
            throw new RuntimeException(__('larawebkit-editor.pexels.error', ['status' => $download->status()]));
        }

        return app(ImagePipeline::class)->store($download->body(), 'pexels-'.$photoId.'.jpg', [
            'title' => $p['alt'] ?? null,
            'alt' => $p['alt'] ?? null,
            'source' => 'pexels',
            'source_id' => (string) $photoId,
            'source_url' => $p['url'] ?? 'https://www.pexels.com/photo/'.$photoId.'/',
            'photographer' => $p['photographer'] ?? null,
            'photographer_url' => $p['photographer_url'] ?? null,
            'uploaded_by' => $userId,
        ], (array) config('larawebkit-media.stock_widths'));
    }

    private function guard(): void
    {
        if (! $this->enabled()) {
            throw new RuntimeException(__('larawebkit-editor.pexels.disabled'));
        }
    }
}

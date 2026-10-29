<?php

namespace App\Modules\Import\WordPress;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Reads a public WordPress site through /wp-json/wp/v2 (100 items per request). */
final class RestSource implements Source
{
    /** @var array<int, array<string, mixed>|null> */
    private array $media = [];

    public function __construct(private string $url)
    {
        $this->url = rtrim($url, '/');
    }

    public function siteUrl(): string
    {
        return $this->url;
    }

    public function all(string $kind): array
    {
        $items = [];
        $page = 1;
        do {
            $response = Http::acceptJson()->timeout(30)->retry(2, 500, throw: false)
                ->get($this->url.'/wp-json/wp/v2/'.$kind, ['per_page' => 100, 'page' => $page, 'context' => 'view']);
            if ($response->status() === 400 && $page > 1) {
                break; // past the last page
            }
            if (! $response->successful()) {
                if ($kind === 'users' && in_array($response->status(), [401, 403, 404], true)) {
                    return []; // many sites hide the users endpoint; authors then fall back
                }
                throw new RuntimeException("WordPress API $kind page $page: HTTP ".$response->status());
            }
            $batch = (array) $response->json();
            array_push($items, ...array_values(array_filter($batch, 'is_array')));
            $pages = (int) ($response->header('X-WP-TotalPages') ?: 1);
        } while ($batch !== [] && ++$page <= $pages);

        return $items;
    }

    public function media(int $id): ?array
    {
        if (! array_key_exists($id, $this->media)) {
            $response = Http::acceptJson()->timeout(30)->get($this->url.'/wp-json/wp/v2/media/'.$id, ['context' => 'view']);
            $this->media[$id] = $response->successful() ? (array) $response->json() : null;
        }

        return $this->media[$id];
    }
}

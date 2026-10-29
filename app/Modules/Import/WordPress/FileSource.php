<?php

namespace App\Modules\Import\WordPress;

use RuntimeException;

/**
 * An export file: {"site": "https://example.com", "posts": [...], "pages": [...], "categories": [...],
 * "tags": [...], "users": [...], "media": [...]}, each list in the REST API's JSON shape (for sites whose
 * API is not reachable: save the /wp-json/wp/v2/... responses into one file).
 */
final class FileSource implements Source
{
    /** @var array<string, mixed> */
    private array $data;

    public function __construct(string $path)
    {
        $json = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        if (! is_array($json) || ! is_string($json['site'] ?? null)) {
            throw new RuntimeException("$path is not a WordPress export file (JSON with a \"site\" URL).");
        }
        $this->data = $json;
    }

    public function siteUrl(): string
    {
        return rtrim((string) $this->data['site'], '/');
    }

    public function all(string $kind): array
    {
        return array_values(array_filter((array) ($this->data[$kind] ?? []), 'is_array'));
    }

    public function media(int $id): ?array
    {
        foreach ((array) ($this->data['media'] ?? []) as $item) {
            if ((int) ($item['id'] ?? 0) === $id) {
                return $item;
            }
        }

        return null;
    }
}

<?php

namespace App\Modules\Import\WordPress;

/** Where WordPress data comes from: the REST API of a live site or an export JSON file. */
interface Source
{
    /** Site URL, e.g. https://example.com (used for links, redirects and the image host check). */
    public function siteUrl(): string;

    /**
     * Every item of a REST collection, in the REST API's JSON shape.
     *
     * @param  'users'|'categories'|'tags'|'pages'|'posts'  $kind
     * @return list<array<string, mixed>>
     */
    public function all(string $kind): array;

    /** @return array<string, mixed>|null one attachment (source_url, alt_text, caption...) */
    public function media(int $id): ?array;
}

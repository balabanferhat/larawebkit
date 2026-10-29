<?php

namespace App\Modules\Editor\Scoring;

/**
 * Everything the scorers look at. Built from a Page/Post model or from the
 * editor form state, so the same object feeds the live and the saved score.
 */
final class ContentInput
{
    /**
     * @param  list<string>  $keywords
     * @param  list<array{question: string, answer: string}>  $faq
     */
    public function __construct(
        public readonly string $title = '',
        public readonly string $html = '',
        public readonly string $metaDescription = '',
        public readonly array $keywords = [],
        public readonly string $locale = 'en',
        public readonly bool $featuredImageHasAlt = false,
        public readonly string $summary = '',
        public readonly array $faq = [],
        public readonly string $sources = '',
        public readonly bool $hasAuthor = false,
        public readonly string $siteHost = '',
    ) {}

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        $keywords = $data['keywords'] ?? [];
        if (is_string($keywords)) {
            $keywords = explode(',', $keywords);
        }

        return new self(
            title: (string) ($data['title'] ?? ''),
            html: (string) ($data['content'] ?? $data['html'] ?? ''),
            metaDescription: (string) ($data['meta_description'] ?? ''),
            keywords: array_values(array_filter(array_map(fn ($k) => trim((string) $k), (array) $keywords), fn ($k) => $k !== '')),
            locale: (string) ($data['locale'] ?? app()->getLocale()),
            featuredImageHasAlt: (bool) ($data['featured_image_has_alt'] ?? false),
            summary: (string) ($data['summary'] ?? ''),
            faq: array_values(array_filter((array) ($data['faq'] ?? []), fn ($i) => is_array($i) && trim((string) ($i['question'] ?? '')) !== '')),
            sources: (string) ($data['sources'] ?? ''),
            hasAuthor: (bool) ($data['has_author'] ?? false),
            siteHost: (string) ($data['site_host'] ?? parse_url((string) config('app.url'), PHP_URL_HOST) ?? ''),
        );
    }
}

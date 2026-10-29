<?php

namespace App\Modules\Editor\Scoring;

/**
 * SEO score (0-100). Mirrors seoScore() in resources/js/larawebkit/scoring.js.
 */
class SeoScorer
{
    /** @param  array<string, array<string, mixed>>|null  $checks */
    public function __construct(private ?array $checks = null)
    {
        $this->checks ??= (array) config('larawebkit-editor.seo.checks', []);
    }

    public function score(ContentInput $input): ScoreResult
    {
        $a = new TextAnalyzer($input->html);
        $results = [];
        $words = $a->wordCount();

        foreach ($this->checks as $key => $cfg) {
            if (! ($cfg['enabled'] ?? true)) {
                continue;
            }
            $results[] = match ($key) {
                'word_count' => $this->tiered($key, $cfg, $words, ['count' => $words]),
                'keyword_in_title' => $this->keywordInTitle($cfg, $input),
                'meta_description' => $this->metaDescription($cfg, $input),
                'h2_count' => $this->tiered($key, $cfg, $a->countTag('h2'), ['count' => $a->countTag('h2')]),
                'keyword_density' => $this->density($cfg, $input, $a),
                'has_list' => $this->binary($key, $cfg, $a->countTag('ul') + $a->countTag('ol') > 0),
                'has_bold' => $this->binary($key, $cfg, $a->countTag('strong') + $a->countTag('b') > 0),
                'image_alt' => $this->binary($key, $cfg, $input->featuredImageHasAlt || $this->hasImageWithAlt($a)),
                'internal_link' => $this->binary($key, $cfg, $this->hasInternalLink($a, $input->siteHost)),
                default => null,
            };
        }

        $results = array_values(array_filter($results));
        $max = array_sum(array_map(fn (Check $c) => $c->max, $results));
        $earned = array_sum(array_map(fn (Check $c) => $c->points, $results));

        return new ScoreResult(
            $max > 0 ? (int) round($earned * 100 / $max) : 0,
            $results,
            ['words' => $words, 'density' => self::densityOf($input, $a)],
        );
    }

    /** @param  array<string, mixed>  $cfg */
    private function tiered(string $key, array $cfg, int $value, array $params): Check
    {
        $tiers = (array) $cfg['tiers'];
        krsort($tiers, SORT_NUMERIC);
        $max = (int) ($cfg['max'] ?? max($tiers));
        foreach ($tiers as $threshold => $points) {
            if ($value >= (int) $threshold) {
                return new Check($key, (int) $points >= $max, (int) $points, $max, __("larawebkit-editor.seo.$key.".((int) $points >= $max ? 'pass' : 'partial'), $params + ['target' => array_key_first($tiers)]));
            }
        }

        return new Check($key, false, (int) ($cfg['fallback'] ?? 0), $max, __("larawebkit-editor.seo.$key.fail", $params + ['target' => array_key_first($tiers)]));
    }

    /** @param  array<string, mixed>  $cfg */
    private function binary(string $key, array $cfg, bool $passed): Check
    {
        $max = (int) $cfg['points'];

        return new Check($key, $passed, $passed ? $max : 0, $max, __("larawebkit-editor.seo.$key.".($passed ? 'pass' : 'fail')));
    }

    /** @param  array<string, mixed>  $cfg */
    private function keywordInTitle(array $cfg, ContentInput $input): Check
    {
        $title = TextAnalyzer::normalize($input->title);
        $found = false;
        foreach ($input->keywords as $kw) {
            $needle = TextAnalyzer::normalize($kw);
            if (trim($needle) !== '' && str_contains($title, $needle)) {
                $found = true;
                break;
            }
        }

        if ($input->keywords === []) {
            return new Check('keyword_in_title', false, 0, (int) $cfg['points'], __('larawebkit-editor.seo.no_keywords'));
        }

        return $this->binary('keyword_in_title', $cfg, $found);
    }

    /** @param  array<string, mixed>  $cfg */
    private function metaDescription(array $cfg, ContentInput $input): Check
    {
        $len = mb_strlen(trim($input->metaDescription));
        $max = (int) $cfg['points'];
        $params = ['length' => $len, 'min' => $cfg['min'], 'max' => $cfg['max']];

        if ($len === 0) {
            return new Check('meta_description', false, 0, $max, __('larawebkit-editor.seo.meta_description.fail', $params));
        }
        if ($len >= $cfg['min'] && $len <= $cfg['max']) {
            return new Check('meta_description', true, $max, $max, __('larawebkit-editor.seo.meta_description.pass', $params));
        }

        return new Check('meta_description', false, (int) $cfg['partial'], $max, __('larawebkit-editor.seo.meta_description.partial', $params));
    }

    /** Density in percent of the primary (first) keyword. */
    public static function densityOf(ContentInput $input, TextAnalyzer $a): float
    {
        $kw = $input->keywords[0] ?? '';
        $total = $a->wordCount();
        $kwWords = count(TextAnalyzer::words($kw));
        if ($kw === '' || $total === 0 || $kwWords === 0) {
            return 0.0;
        }

        return $a->countPhrase($kw) * $kwWords * 100 / $total;
    }

    /** @param  array<string, mixed>  $cfg */
    private function density(array $cfg, ContentInput $input, TextAnalyzer $a): Check
    {
        $density = self::densityOf($input, $a);
        $max = (int) $cfg['points'];
        $params = ['density' => number_format($density, 1), 'min' => $cfg['min'], 'max' => $cfg['max']];

        if ($density <= 0) {
            return new Check('keyword_density', false, 0, $max, __('larawebkit-editor.seo.keyword_density.fail', $params));
        }
        if ($density >= $cfg['min'] && $density <= $cfg['max']) {
            return new Check('keyword_density', true, $max, $max, __('larawebkit-editor.seo.keyword_density.pass', $params));
        }

        return new Check('keyword_density', false, (int) $cfg['partial'], $max, __('larawebkit-editor.seo.keyword_density.partial', $params));
    }

    private function hasImageWithAlt(TextAnalyzer $a): bool
    {
        foreach ($a->images() as $img) {
            if ($img['alt'] !== '') {
                return true;
            }
        }

        return false;
    }

    private function hasInternalLink(TextAnalyzer $a, string $host): bool
    {
        foreach ($a->links() as $href) {
            if (str_starts_with($href, '/') && ! str_starts_with($href, '//')) {
                return true;
            }
            $linkHost = parse_url($href, PHP_URL_HOST);
            if ($host !== '' && is_string($linkHost) && strcasecmp(preg_replace('/^www\./i', '', $linkHost), preg_replace('/^www\./i', '', $host)) === 0) {
                return true;
            }
        }

        return false;
    }
}

<?php

namespace App\Modules\Editor\Scoring;

/**
 * GEO check (0-100): how easy the content is to quote for AI search engines.
 * Mirrors geoScore() in resources/js/larawebkit/scoring.js.
 */
class GeoChecker
{
    /** @param  array<string, array<string, mixed>>|null  $checks */
    public function __construct(private ?array $checks = null)
    {
        $this->checks ??= (array) config('larawebkit-editor.geo.checks', []);
    }

    public function score(ContentInput $input): ScoreResult
    {
        $a = new TextAnalyzer($input->html);
        $results = [];

        foreach ($this->checks as $key => $cfg) {
            if (! ($cfg['enabled'] ?? true)) {
                continue;
            }
            $passed = match ($key) {
                'has_summary' => trim($input->summary) !== '',
                'question_h2' => $this->hasQuestionHeading($a),
                'faq_or_lists' => $input->faq !== [] || $a->countTag('ul') + $a->countTag('ol') + $a->countTag('table') > 0,
                'has_sources' => trim(strip_tags($input->sources)) !== '',
                'has_author' => $input->hasAuthor,
                'answer_first' => $this->answerFirst($a, $cfg),
                default => null,
            };
            if ($passed === null) {
                continue;
            }
            $max = (int) $cfg['points'];
            $results[] = new Check($key, $passed, $passed ? $max : 0, $max, __("larawebkit-editor.geo.$key.".($passed ? 'pass' : 'fail'), [
                'min' => $cfg['min_words'] ?? '', 'max' => $cfg['max_words'] ?? '',
            ]));
        }

        $max = array_sum(array_map(fn (Check $c) => $c->max, $results));
        $earned = array_sum(array_map(fn (Check $c) => $c->points, $results));

        return new ScoreResult($max > 0 ? (int) round($earned * 100 / $max) : 0, $results);
    }

    private function hasQuestionHeading(TextAnalyzer $a): bool
    {
        foreach ($a->headings('h2') as $heading) {
            if (str_ends_with(rtrim($heading), '?')) {
                return true;
            }
        }

        return false;
    }

    /** @param  array<string, mixed>  $cfg */
    private function answerFirst(TextAnalyzer $a, array $cfg): bool
    {
        $words = count(TextAnalyzer::words($a->firstParagraph()));

        return $words >= (int) $cfg['min_words'] && $words <= (int) $cfg['max_words'];
    }
}

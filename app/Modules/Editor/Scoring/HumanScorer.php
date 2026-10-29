<?php

namespace App\Modules\Editor\Scoring;

/**
 * Human-ness score (0-100) from local heuristics only. Starts at 100.
 * Mirrors humanScore() in resources/js/larawebkit/scoring.js.
 */
class HumanScorer
{
    /** @param  array<string, mixed>|null  $config */
    public function __construct(private ?array $config = null)
    {
        $this->config ??= (array) config('larawebkit-editor.human', []);
    }

    /** @return list<string> */
    public static function clichesFor(string $locale): array
    {
        $list = config("larawebkit-editor.cliches.$locale");
        if (! is_array($list)) {
            $path = config_path("larawebkit-editor/cliches/$locale.php");
            $list = is_file($path) ? require $path : [];
        }

        return array_values(array_map('strval', $list));
    }

    /** @param  list<string>|null  $cliches */
    public function score(ContentInput $input, ?array $cliches = null): ScoreResult
    {
        $c = $this->config;
        $a = new TextAnalyzer($input->html);
        $cliches ??= self::clichesFor($input->locale);
        $score = (int) ($c['start'] ?? 100);
        $checks = [];

        // 1. Sentence length variation (coefficient of variation).
        $lengths = array_map(fn (string $s) => count(TextAnalyzer::words($s)), $a->sentences);
        $n = count($lengths);
        $mean = $n > 0 ? array_sum($lengths) / $n : 0.0;
        $cv = null;
        if ($n >= (int) ($c['min_sentences_for_variation'] ?? 3) && $mean > 0) {
            $variance = array_sum(array_map(fn ($l) => ($l - $mean) ** 2, $lengths)) / $n;
            $cv = sqrt($variance) / $mean;
            $penalty = 0;
            foreach ((array) $c['variation'] as $rule) {
                if ($cv < $rule['below']) {
                    $penalty = (int) $rule['penalty'];
                    break;
                }
            }
            $score -= $penalty;
            $checks[] = new Check('variation', $penalty === 0, -$penalty, 0, __('larawebkit-editor.human.variation.'.($penalty === 0 ? 'pass' : 'fail'), ['cv' => number_format($cv, 2)]));
        }

        // 2. Very long sentences on average.
        $long = $mean > (float) $c['long_sentences']['average_above'];
        if ($long) {
            $score -= (int) $c['long_sentences']['penalty'];
        }
        $checks[] = new Check('long_sentences', ! $long, $long ? -(int) $c['long_sentences']['penalty'] : 0, 0, __('larawebkit-editor.human.long_sentences.'.($long ? 'fail' : 'pass'), ['average' => number_format($mean, 1)]));

        // 3. Clichés from the locale list.
        $found = [];
        foreach ($cliches as $phrase) {
            if ($a->containsPhrase($phrase)) {
                $found[] = $phrase;
            }
        }
        $clichePenalty = min(count($found) * (int) $c['cliches']['penalty'], (int) $c['cliches']['max_penalty']);
        $score -= $clichePenalty;
        $checks[] = new Check('cliches', $found === [], -$clichePenalty, 0, $found === []
            ? __('larawebkit-editor.human.cliches.pass')
            : __('larawebkit-editor.human.cliches.fail', ['list' => implode(', ', $found)]));

        // 4. Runs of sentences starting with the same word.
        $runs = 0;
        $runLength = 0;
        $prev = null;
        $minRun = (int) $c['repeated_starts']['min_run'];
        foreach ($a->sentences as $sentence) {
            $first = TextAnalyzer::words($sentence)[0] ?? '';
            if ($first === $prev) {
                $runLength++;
            } else {
                if ($runLength >= $minRun) {
                    $runs++;
                }
                $runLength = 1;
                $prev = $first;
            }
        }
        if ($runLength >= $minRun) {
            $runs++;
        }
        $repeatPenalty = min($runs * (int) $c['repeated_starts']['penalty'], (int) $c['repeated_starts']['max_penalty']);
        $score -= $repeatPenalty;
        $checks[] = new Check('repeated_starts', $runs === 0, -$repeatPenalty, 0, __('larawebkit-editor.human.repeated_starts.'.($runs === 0 ? 'pass' : 'fail'), ['count' => $runs]));

        // 5. Bonuses: a question, addressing the reader.
        $hasQuestion = str_contains($a->text, '?');
        if ($hasQuestion) {
            $score += (int) $c['question_bonus'];
        }
        $checks[] = new Check('question', $hasQuestion, $hasQuestion ? (int) $c['question_bonus'] : 0, (int) $c['question_bonus'], __('larawebkit-editor.human.question.'.($hasQuestion ? 'pass' : 'fail')));

        $readerWords = (array) ($c['reader_words'][$input->locale] ?? []);
        $addresses = array_intersect(array_map(fn ($w) => mb_strtolower($w), $readerWords), $a->words) !== [];
        if ($addresses) {
            $score += (int) $c['reader_bonus'];
        }
        $checks[] = new Check('reader', $addresses, $addresses ? (int) $c['reader_bonus'] : 0, (int) $c['reader_bonus'], __('larawebkit-editor.human.reader.'.($addresses ? 'pass' : 'fail')));

        return new ScoreResult(max(0, min(100, $score)), $checks, [
            'sentences' => $n,
            'average_sentence' => round($mean, 2),
            'cv' => $cv === null ? null : round($cv, 4),
            'cliches' => $found,
        ]);
    }
}

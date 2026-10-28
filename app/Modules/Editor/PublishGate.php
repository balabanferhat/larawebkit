<?php

namespace App\Modules\Editor;

use App\Models\User;
use App\Modules\Editor\Scoring\ContentInput;

/**
 * Minimum quality before publishing (SEO, word count, human score).
 * Users with "override publish gate" can publish anyway with a logged reason.
 */
class PublishGate
{
    public function __construct(private ContentScorer $scorer) {}

    public function enabled(): bool
    {
        return (bool) config('larawebkit-editor.publish_gate.enabled', true) && (bool) setting('features.publish_gate', true);
    }

    /** @return array{min_seo: int, min_words: int, min_human: int, enabled: bool} */
    public function thresholds(): array
    {
        return [
            'enabled' => $this->enabled(),
            'min_seo' => (int) config('larawebkit-editor.publish_gate.min_seo', 60),
            'min_words' => (int) config('larawebkit-editor.publish_gate.min_words', 300),
            'min_human' => (int) config('larawebkit-editor.publish_gate.min_human', 45),
        ];
    }

    /** @return list<string> Human-readable reasons the content may not be published (empty = OK). */
    public function failures(ContentInput $input): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $t = $this->thresholds();
        $scores = $this->scorer->score($input);
        $words = (int) ($scores['seo']->meta['words'] ?? 0);
        $out = [];

        if ($scores['seo']->score < $t['min_seo']) {
            $out[] = __('larawebkit-editor.gate.seo', ['score' => $scores['seo']->score, 'min' => $t['min_seo']]);
        }
        if ($words < $t['min_words']) {
            $out[] = __('larawebkit-editor.gate.words', ['count' => $words, 'min' => $t['min_words']]);
        }
        if ($scores['human']->score < $t['min_human']) {
            $out[] = __('larawebkit-editor.gate.human', ['score' => $scores['human']->score, 'min' => $t['min_human']]);
        }

        return $out;
    }

    public function canOverride(?User $user): bool
    {
        return (bool) $user?->can('override publish gate');
    }
}

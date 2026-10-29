<?php

namespace App\Modules\Editor\Scoring;

final class ScoreResult
{
    /** @param  list<Check>  $checks */
    public function __construct(
        public readonly int $score,
        public readonly array $checks,
        public readonly array $meta = [],
    ) {}

    public function check(string $key): ?Check
    {
        foreach ($this->checks as $check) {
            if ($check->key === $key) {
                return $check;
            }
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'checks' => array_map(fn (Check $c) => $c->toArray(), $this->checks),
            'meta' => $this->meta,
        ];
    }
}

<?php

namespace App\Modules\Editor\Scoring;

final class Check
{
    public function __construct(
        public readonly string $key,
        public readonly bool $passed,
        public readonly int $points,
        public readonly int $max,
        public readonly string $hint = '',
    ) {}

    /** @return array{key: string, passed: bool, points: int, max: int, hint: string} */
    public function toArray(): array
    {
        return ['key' => $this->key, 'passed' => $this->passed, 'points' => $this->points, 'max' => $this->max, 'hint' => $this->hint];
    }
}

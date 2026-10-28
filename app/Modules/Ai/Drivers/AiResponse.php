<?php

namespace App\Modules\Ai\Drivers;

final class AiResponse
{
    public function __construct(
        public readonly string $text,
        public readonly int $inputTokens = 0,
        public readonly int $outputTokens = 0,
        public readonly string $model = '',
    ) {}
}

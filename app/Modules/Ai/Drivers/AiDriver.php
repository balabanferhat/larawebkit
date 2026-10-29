<?php

namespace App\Modules\Ai\Drivers;

interface AiDriver
{
    /** @throws \Throwable on any provider error (the manager then tries the next provider) */
    public function complete(string $system, string $prompt, string $model, int $maxTokens): AiResponse;
}

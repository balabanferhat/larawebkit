<?php

namespace App\Modules\Ai\Drivers;

use GuzzleHttp\Client;
use OpenAI;

/**
 * OpenAI, DeepSeek and local servers (Ollama, LM Studio) through the
 * OpenAI-compatible chat completions API (openai-php/client).
 */
class OpenAiCompatibleDriver implements AiDriver
{
    public function __construct(private string $apiKey, private ?string $baseUrl = null, private int $timeout = 120) {}

    public function complete(string $system, string $prompt, string $model, int $maxTokens): AiResponse
    {
        $factory = OpenAI::factory()->withApiKey($this->apiKey)
            ->withHttpClient(new Client(['timeout' => $this->timeout]));
        if ($this->baseUrl) {
            $factory = $factory->withBaseUri($this->baseUrl);
        }

        $response = $factory->make()->chat()->create([
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return new AiResponse(
            trim((string) ($response->choices[0]->message->content ?? '')),
            (int) ($response->usage?->promptTokens ?? 0),
            (int) ($response->usage?->completionTokens ?? 0),
            $model,
        );
    }
}

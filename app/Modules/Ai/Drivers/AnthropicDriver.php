<?php

namespace App\Modules\Ai\Drivers;

use Anthropic\Client;
use RuntimeException;

/**
 * Claude via the official Anthropic PHP SDK (anthropic-ai/sdk).
 */
class AnthropicDriver implements AiDriver
{
    public function __construct(private string $apiKey, private int $timeout = 120) {}

    public function complete(string $system, string $prompt, string $model, int $maxTokens): AiResponse
    {
        $client = new Client(apiKey: $this->apiKey, requestOptions: ['timeout' => $this->timeout]);

        $message = $client->messages->create(
            model: $model,
            maxTokens: $maxTokens,
            system: $system,
            messages: [['role' => 'user', 'content' => $prompt]],
        );

        if (($message->stopReason ?? null) === 'refusal') {
            throw new RuntimeException('The model declined this request.');
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        return new AiResponse(trim($text), $message->usage->inputTokens, $message->usage->outputTokens, $model);
    }
}

<?php

namespace App\Modules\Ai;

use App\Models\User;
use App\Modules\Ai\Drivers\AiDriver;
use App\Modules\Ai\Drivers\AnthropicDriver;
use App\Modules\Ai\Drivers\OpenAiCompatibleDriver;
use App\Modules\Ai\Models\AiUsage;
use App\Modules\Core\ModuleRegistry;
use RuntimeException;
use Throwable;

/**
 * Runs an AI action through the first configured provider, falling back to
 * the next one on failure, and logs usage + estimated cost. Every AI feature
 * checks available() first, so missing keys simply hide the feature.
 */
class AiManager
{
    /** @var array<string, AiDriver> */
    private array $drivers = [];

    public function __construct(private PromptRepository $prompts) {}

    /** Swap in a driver (used by tests and by projects adding providers). */
    public function extend(string $provider, AiDriver $driver): void
    {
        $this->drivers[$provider] = $driver;
    }

    /** @return list<string> providers that have credentials, in fallback order */
    public function configuredProviders(): array
    {
        return array_values(array_filter((array) config('larawebkit-ai.order'), fn (string $p) => isset($this->drivers[$p]) || filled(config("larawebkit-ai.providers.$p.api_key"))));
    }

    public function enabled(): bool
    {
        return ModuleRegistry::enabled('ai') && (bool) setting('features.ai_assistant', true) && $this->configuredProviders() !== [];
    }

    public function overBudget(): bool
    {
        $budget = (float) setting('ai.monthly_budget_usd', 0);

        return $budget > 0 && AiUsage::spentThisMonth() >= $budget;
    }

    public function available(string $action): bool
    {
        return $this->enabled() && array_key_exists($action, (array) config('larawebkit-ai.actions')) && ! $this->overBudget();
    }

    public function queued(string $action): bool
    {
        return (bool) config("larawebkit-ai.actions.$action.queue", false);
    }

    /** @param  array<string, mixed>  $input */
    public function run(string $action, array $input, ?User $user = null, ?string $locale = null): string
    {
        if (! $this->enabled()) {
            throw new RuntimeException(__('larawebkit-ai.errors.disabled'));
        }
        if ($this->overBudget()) {
            throw new RuntimeException(__('larawebkit-ai.errors.budget'));
        }
        $config = config("larawebkit-ai.actions.$action") ?? throw new RuntimeException("Unknown AI action [$action].");
        $prompt = $this->prompts->render($action, $input, $locale);

        $last = null;
        foreach ($this->configuredProviders() as $provider) {
            $model = (string) config("larawebkit-ai.providers.$provider.models.{$config['tier']}");
            try {
                $response = $this->driver($provider)->complete($prompt['system'], $prompt['user'], $model, (int) $config['max_tokens']);
                $this->log($action, $provider, $model, $response->inputTokens, $response->outputTokens, true, $user);

                return $response->text;
            } catch (Throwable $e) {
                // Only the class and provider are logged: never prompts, keys or outputs.
                report(new RuntimeException("AI provider [$provider] failed for [$action]: ".class_basename($e), 0, $e));
                $this->log($action, $provider, $model, 0, 0, false, $user);
                $last = $e;
            }
        }

        throw new RuntimeException(__('larawebkit-ai.errors.failed'), 0, $last);
    }

    public static function cost(string $model, int $in, int $out): float
    {
        [$pIn, $pOut] = config("larawebkit-ai.pricing.$model") ?? [0, 0];

        return round(($in * $pIn + $out * $pOut) / 1_000_000, 6);
    }

    private function log(string $action, string $provider, string $model, int $in, int $out, bool $ok, ?User $user): void
    {
        AiUsage::create([
            'action' => $action,
            'provider' => $provider,
            'model' => $model,
            'input_tokens' => $in,
            'output_tokens' => $out,
            'cost_usd' => self::cost($model, $in, $out),
            'succeeded' => $ok,
            'user_id' => $user?->getKey() ?? auth()->id(),
            'created_at' => now(),
        ]);
    }

    private function driver(string $provider): AiDriver
    {
        if (isset($this->drivers[$provider])) {
            return $this->drivers[$provider];
        }
        $cfg = (array) config("larawebkit-ai.providers.$provider");
        $timeout = (int) config('larawebkit-ai.timeout', 120);

        return $this->drivers[$provider] = match ($cfg['driver'] ?? null) {
            'anthropic' => new AnthropicDriver((string) $cfg['api_key'], $timeout),
            'openai' => new OpenAiCompatibleDriver((string) $cfg['api_key'], $cfg['base_url'] ?? null, $timeout),
            default => throw new RuntimeException("Unknown AI driver for [$provider]."),
        };
    }
}

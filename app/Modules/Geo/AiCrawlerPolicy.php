<?php

namespace App\Modules\Geo;

/**
 * Decides which AI crawlers may fetch the site and writes them into robots.txt.
 * Presets: allow_all, search_only (allow AI search, block AI training), block_all, custom.
 */
class AiCrawlerPolicy
{
    /** @return array<string, array{purpose: string, operator: string}> */
    public static function bots(): array
    {
        return (array) config('larawebkit-geo.bots', []);
    }

    /** @return array<string, bool> bot => allowed */
    public function decisions(?string $preset = null, ?array $custom = null): array
    {
        $preset ??= (string) setting('geo.ai_preset', 'search_only');
        $custom ??= (array) setting('geo.ai_bots', []);

        $out = [];
        foreach (self::bots() as $name => $bot) {
            $out[$name] = match ($preset) {
                'allow_all' => true,
                'block_all' => false,
                'custom' => in_array($name, $custom, true),
                default => $bot['purpose'] === 'search',
            };
        }

        return $out;
    }

    public function allows(string $bot): bool
    {
        return $this->decisions()[$bot] ?? true;
    }

    /** robots.txt groups for every known AI crawler. */
    public function robotsRules(): string
    {
        $lines = [];
        foreach ($this->decisions() as $bot => $allowed) {
            $lines[] = "User-agent: $bot";
            $lines[] = $allowed ? 'Allow: /' : 'Disallow: /';
            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}

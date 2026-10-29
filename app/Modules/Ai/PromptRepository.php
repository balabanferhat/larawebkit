<?php

namespace App\Modules\Ai;

use RuntimeException;

/**
 * Versioned, locale-aware prompt files: resources/prompts/{action}.{locale}.md
 * falling back to {action}.md. A file has a "# system" and a "# user" section;
 * {{placeholders}} are replaced with the action's input.
 */
class PromptRepository
{
    /** @return array{system: string, user: string, version: string} */
    public function render(string $action, array $vars, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $raw = $this->load($action, $locale);

        $version = preg_match('/^<!--\s*version:\s*([^\s]+)\s*-->/m', $raw, $m) ? $m[1] : '1';
        $parts = preg_split('/^#\s*user\s*$/mi', $raw, 2);
        $system = preg_replace('/^#\s*system\s*$/mi', '', $parts[0] ?? '');
        $system = preg_replace('/^<!--.*?-->\s*/s', '', (string) $system);

        $vars += ['language' => config("larawebkit.locales.$locale", $locale), 'site_name' => (string) setting('general.site_name')];
        $replace = fn (string $text) => preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', fn ($m) => is_scalar($vars[$m[1]] ?? null) ? (string) $vars[$m[1]] : '', $text);

        return ['system' => trim($replace((string) $system)), 'user' => trim($replace((string) ($parts[1] ?? ''))), 'version' => $version];
    }

    private function load(string $action, string $locale): string
    {
        if (! preg_match('/^[a-z_]+$/', $action)) {
            throw new RuntimeException("Invalid prompt name [$action].");
        }
        foreach ((array) config('larawebkit-ai.prompt_paths') as $dir) {
            foreach (["$action.$locale.md", "$action.md"] as $file) {
                if (is_file("$dir/$file")) {
                    return (string) file_get_contents("$dir/$file");
                }
            }
        }

        throw new RuntimeException("Prompt [$action] not found.");
    }
}

<?php

namespace App\Modules\Blocks;

use App\Modules\Blocks\Types\Faq;
use App\Modules\Themes\ThemeManager;
use Illuminate\Support\Facades\View;

/**
 * The block types from config/larawebkit-blocks.php, filtered by what the
 * active theme supports (theme.json "supports.blocks"), and helpers to turn
 * stored blocks into text, FAQ items and media ids.
 */
class BlockRegistry
{
    /** @return array<string, class-string<Block>> */
    public function all(): array
    {
        $out = [];
        foreach ((array) config('larawebkit-blocks.types', []) as $class) {
            if (is_subclass_of($class, Block::class)) {
                $out[$class::key()] = $class;
            }
        }

        return $out;
    }

    /** @return array<string, class-string<Block>> types available in the active theme */
    public function available(): array
    {
        $themes = app()->bound(ThemeManager::class) ? app(ThemeManager::class) : null;

        return array_filter($this->all(), fn ($class, $key) => ! $themes || $themes->supportsBlock($key), ARRAY_FILTER_USE_BOTH);
    }

    /** @return class-string<Block>|null */
    public function get(string $type): ?string
    {
        return $this->all()[$type] ?? null;
    }

    /** View for a block: the theme's override (theme::blocks.{key}) or the shared component. */
    public function view(string $type): string
    {
        if (app()->bound(ThemeManager::class)) {
            app(ThemeManager::class)->register();
            if (View::exists("theme::blocks.$type")) {
                return "theme::blocks.$type";
            }
        }

        return "components.lwk.blocks.$type";
    }

    /**
     * Normalise stored builder state: [{type, data}], unknown types dropped.
     *
     * @return list<array{type: string, data: array<string, mixed>}>
     */
    public function normalize(mixed $blocks): array
    {
        if (is_string($blocks)) {
            $blocks = json_decode($blocks, true);
        }

        return array_values(array_filter(array_map(
            fn ($b) => is_array($b) && isset($b['type']) && $this->get((string) $b['type']) ? ['type' => (string) $b['type'], 'data' => (array) ($b['data'] ?? [])] : null,
            (array) $blocks,
        )));
    }

    /**
     * Markdown-ish plain text of all blocks (Markdown twin, llms-full.txt, scores, search).
     *
     * @param  list<string>  $except  block types to leave out
     */
    public function text(mixed $blocks, array $except = []): string
    {
        $parts = [];
        foreach ($this->normalize($blocks) as $block) {
            if (in_array($block['type'], $except, true)) {
                continue;
            }
            $class = $this->get($block['type']);
            if ($text = trim($class::text($block['data']))) {
                $parts[] = $text;
            }
        }

        return implode("\n\n", $parts);
    }

    /** Simple HTML for the content scorer (headings and paragraphs from the text). */
    public function html(mixed $blocks): string
    {
        $html = '';
        foreach (preg_split("/\n{2,}/", $this->text($blocks)) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }
            $html .= match (true) {
                str_starts_with($chunk, '### ') => '<h3>'.e(substr($chunk, 4)).'</h3>',
                str_starts_with($chunk, '## '), str_starts_with($chunk, '# ') => '<h2>'.e(ltrim($chunk, '# ')).'</h2>',
                default => '<p>'.e($chunk).'</p>',
            };
        }

        return $html;
    }

    /** @return list<array{question: string, answer: string}> */
    public function faq(mixed $blocks): array
    {
        $out = [];
        foreach ($this->normalize($blocks) as $block) {
            if ($block['type'] === Faq::key()) {
                $out = [...$out, ...Faq::questions($block['data'])];
            }
        }

        return $out;
    }

    /** @return list<int> every media id referenced by the blocks */
    public function mediaIds(mixed $blocks): array
    {
        $ids = [];
        $walk = function ($value, ?string $key) use (&$walk, &$ids) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $walk($v, is_string($k) ? $k : $key);
                }
            } elseif (in_array($key, ['image', 'photo', 'poster', 'background_image', 'images'], true) && is_numeric($value)) {
                $ids[] = (int) $value;
            }
        };
        foreach ($this->normalize($blocks) as $block) {
            $walk($block['data'], null);
        }

        return array_values(array_unique($ids));
    }
}

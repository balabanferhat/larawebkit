<?php

namespace App\Modules\Blocks;

/**
 * Page presets: a starting set of blocks with placeholder text in the current
 * language, chosen when a page is created ("Landing page", "About", ...).
 */
final class BlockPresets
{
    /** @return array<string, string> */
    public static function options(): array
    {
        return collect((array) config('larawebkit-blocks.presets', []))
            ->mapWithKeys(fn ($p) => [$p => __("larawebkit-blocks.presets.$p.label")])->all();
    }

    /** @return list<array{type: string, data: array<string, mixed>}> */
    public static function blocks(string $preset): array
    {
        $t = fn (string $key) => __("larawebkit-blocks.presets.$preset.$key");
        $items = fn (string $key, int $n, array $fields) => array_map(
            fn ($i) => collect($fields)->mapWithKeys(fn ($f, $k) => [is_string($k) ? $k : $f => is_string($k) ? $f : __("larawebkit-blocks.presets.$preset.$key.$i.$f")])->all(),
            range(1, $n),
        );
        $buttons = fn (string $label, string $url, string $label2 = '', string $url2 = '') => array_values(array_filter([
            ['label' => $t($label), 'url' => $url, 'style' => 'primary'],
            $label2 !== '' ? ['label' => $t($label2), 'url' => $url2, 'style' => 'secondary'] : null,
        ]));

        return match ($preset) {
            'landing' => [
                ['type' => 'hero', 'data' => ['variant' => 'split', 'eyebrow' => $t('eyebrow'), 'heading' => $t('heading'), 'text' => $t('text'), 'buttons' => $buttons('cta', '/contact', 'cta2', '#features')]],
                ['type' => 'logos', 'data' => ['variant' => 'row', 'heading' => $t('logos'), 'logos' => []]],
                ['type' => 'features', 'data' => ['variant' => 'grid', 'anchor' => 'features', 'heading' => $t('features'), 'items' => $items('feature', 3, ['title', 'text', 'icon' => 'sparkles'])]],
                ['type' => 'stats', 'data' => ['variant' => 'row', 'background' => 'muted', 'items' => $items('stat', 3, ['value', 'label'])]],
                ['type' => 'testimonials', 'data' => ['variant' => 'grid', 'heading' => $t('testimonials'), 'items' => $items('quote', 2, ['quote', 'name', 'role'])]],
                ['type' => 'faq', 'data' => ['variant' => 'accordion', 'heading' => $t('faq'), 'items' => $items('question', 3, ['question', 'answer'])]],
                ['type' => 'cta', 'data' => ['variant' => 'banner', 'background' => 'brand', 'heading' => $t('final'), 'text' => $t('final_text'), 'buttons' => $buttons('cta', '/contact')]],
            ],
            'about' => [
                ['type' => 'hero', 'data' => ['variant' => 'centered', 'eyebrow' => $t('eyebrow'), 'heading' => $t('heading'), 'text' => $t('text')]],
                ['type' => 'rich_text', 'data' => ['variant' => 'narrow', 'content' => '<h2>'.e($t('story')).'</h2><p>'.e($t('story_text')).'</p>']],
                ['type' => 'stats', 'data' => ['variant' => 'cards', 'items' => $items('stat', 3, ['value', 'label'])]],
                ['type' => 'team', 'data' => ['variant' => 'grid', 'heading' => $t('team'), 'members' => $items('member', 3, ['name', 'role'])]],
                ['type' => 'cta', 'data' => ['variant' => 'boxed', 'heading' => $t('final'), 'buttons' => $buttons('cta', '/contact')]],
            ],
            'services' => [
                ['type' => 'hero', 'data' => ['variant' => 'centered', 'eyebrow' => $t('eyebrow'), 'heading' => $t('heading'), 'text' => $t('text'), 'buttons' => $buttons('cta', '/contact')]],
                ['type' => 'features', 'data' => ['variant' => 'cards', 'heading' => $t('features'), 'items' => $items('service', 3, ['title', 'text', 'icon' => 'briefcase'])]],
                ['type' => 'pricing', 'data' => ['variant' => 'cards', 'heading' => $t('pricing'), 'plans' => $items('plan', 3, ['name', 'price', 'period', 'description', 'features', 'button_label' => $t('cta'), 'button_url' => '/contact'])]],
                ['type' => 'faq', 'data' => ['variant' => 'columns', 'heading' => $t('faq'), 'items' => $items('question', 2, ['question', 'answer'])]],
            ],
            'contact' => [
                ['type' => 'hero', 'data' => ['variant' => 'centered', 'heading' => $t('heading'), 'text' => $t('text')]],
                ['type' => 'form', 'data' => ['variant' => 'split', 'form' => 'contact', 'heading' => $t('form'), 'text' => $t('form_text')]],
                ['type' => 'faq', 'data' => ['variant' => 'accordion', 'heading' => $t('faq'), 'items' => $items('question', 2, ['question', 'answer'])]],
            ],
            // A magazine front page: lead story with two secondary stories and a "latest" rail, then the newsletter.
            'magazine' => [
                ['type' => 'latest_posts', 'data' => ['variant' => 'front', 'heading' => $t('top'), 'count' => 8]],
                ['type' => 'latest_posts', 'data' => ['variant' => 'cards', 'heading' => $t('more'), 'count' => 6, 'offset' => 8]],
                ['type' => 'newsletter', 'data' => ['variant' => 'inline', 'background' => 'muted', 'heading' => $t('newsletter'), 'text' => $t('newsletter_text')]],
            ],
            // Not offered in the page editor: themes put these under archives and on the 404 page.
            'archive' => [
                ['type' => 'newsletter', 'data' => ['variant' => 'inline', 'background' => 'muted', 'spacing' => 'compact', 'heading' => $t('newsletter'), 'text' => $t('newsletter_text')]],
            ],
            'not_found' => [
                ['type' => 'latest_posts', 'data' => ['variant' => 'cards', 'heading' => $t('latest'), 'count' => 3]],
            ],
            default => [],
        };
    }
}

<?php

namespace App\Modules\Themes\Design;

use App\Modules\Themes\ThemeManager;

/**
 * Turns the Appearance settings (brand colours, font pair, radius) plus the
 * active theme's defaults into CSS custom properties. Themes only read these
 * variables, so a brand change takes effect without rebuilding assets.
 */
class DesignTokens
{
    public const RADII = [
        'none' => ['0', '0', '0'],
        'small' => ['.25rem', '.375rem', '.5rem'],
        'medium' => ['.375rem', '.75rem', '1rem'],
        'large' => ['.5rem', '1rem', '1.5rem'],
    ];

    public function __construct(private ThemeManager $themes) {}

    /** @return array<string, string> resolved values (setting or theme default) */
    public function values(): array
    {
        $defaults = $this->themes->defaults();
        $pick = fn (string $key, string $default) => filled($v = setting("appearance.$key")) && $v !== 'theme' ? (string) $v : $default;

        $primary = $pick('primary_color', $defaults['primary']);
        $accent = $pick('accent_color', $defaults['accent']);

        return [
            'primary' => Color::valid($primary) ? $primary : $defaults['primary'],
            'accent' => Color::valid($accent) ? $accent : $defaults['accent'],
            'font_pair' => array_key_exists($f = $pick('font_pair', $defaults['font_pair']), Fonts::PAIRS) ? $f : $defaults['font_pair'],
            'radius' => array_key_exists($r = $pick('radius', $defaults['radius']), self::RADII) ? $r : $defaults['radius'],
            'color_scheme' => in_array($s = (string) setting('appearance.color_scheme', 'system'), ['system', 'light', 'dark'], true) ? $s : 'system',
        ];
    }

    /** @return array{light: array<string, string>, dark: array<string, string>} */
    public function variables(): array
    {
        $defaults = $this->themes->defaults();
        $values = $this->values();
        $brand = Color::hex($values['primary']);
        $accent = Color::hex($values['accent']);
        $pair = Fonts::pair($values['font_pair']);
        [$sm, $md, $lg] = self::RADII[$values['radius']];

        // Navigation, buttons and labels use a sans family from the pair, else the system stack.
        $sans = fn (?string $family) => $family !== null && Fonts::FAMILIES[$family]['fallback'] === Fonts::SANS;
        $common = [
            '--font-heading' => Fonts::stack($pair['heading']),
            '--font-body' => Fonts::stack($pair['body']),
            '--font-ui' => Fonts::stack($sans($pair['body']) ? $pair['body'] : ($sans($pair['heading']) ? $pair['heading'] : null)),
            '--radius-sm' => $sm,
            '--radius' => $md,
            '--radius-lg' => $lg,
        ];

        $schemes = [];
        foreach (['light' => $defaults['light'], 'dark' => $defaults['dark']] as $scheme => $neutral) {
            $bg = Color::hex($neutral['bg']);
            $soft = Color::hex($neutral['bg_soft']);
            $text = Color::hex($neutral['text']);
            $surface = Color::hex($neutral['surface']);
            // Text colours must read on every background they sit on (page, soft panels, cards, the
            // consent banner): adjust them against the one each colour contrasts least with.
            $readable = function (Color $color) use ($bg, $soft, $surface): Color {
                foreach ([$soft, $surface, $bg] as $background) {
                    $color = $color->readableOn($background, 4.5);
                }

                return $color;
            };
            // Buttons keep the exact brand colour in light mode; in dark mode a too-dark
            // brand is lifted so the button still stands out from the background.
            $button = $scheme === 'dark' && $brand->contrast($bg) < 3 ? $brand->readableOn($bg, 3) : $brand;
            $accentFill = $scheme === 'dark' && $accent->contrast($bg) < 3 ? $accent->readableOn($bg, 3) : $accent;

            $schemes[$scheme] = [
                '--bg' => $bg->toHex(),
                '--bg-soft' => $soft->toHex(),
                '--surface' => $surface->toHex(),
                '--text' => $text->toHex(),
                '--muted' => $readable(Color::hex($neutral['muted']))->toHex(),
                '--border' => Color::hex($neutral['border'])->toHex(),
                '--brand' => $button->toHex(),
                '--on-brand' => $button->onColor()->toHex(),
                '--brand-soft' => $brand->mix($bg, $scheme === 'dark' ? 0.82 : 0.9)->toHex(),
                '--brand-strong' => $brand->readableOn($bg->mix($brand, 0.1), 4.5)->toHex(),
                '--link' => $readable($brand)->toHex(),
                '--accent' => $accentFill->toHex(),
                '--on-accent' => $accentFill->onColor()->toHex(),
                '--accent-text' => $readable($accent)->toHex(),
            ] + ($scheme === 'light' ? $common : []);
        }

        return $schemes;
    }

    public function css(): string
    {
        $vars = $this->variables();
        $block = fn (array $v) => implode(';', array_map(fn ($k, $val) => "$k:$val", array_keys($v), $v));

        return Fonts::faceCss($this->values()['font_pair'])
            .':root{'.$block($vars['light']).';color-scheme:light}'
            .':root[data-theme="dark"]{'.$block($vars['dark']).';color-scheme:dark}';
    }
}

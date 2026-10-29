<?php

namespace App\Modules\Themes\Design;

use Illuminate\Support\HtmlString;

/**
 * Self-hosted variable fonts (public/fonts/lwk, SIL OFL, from Fontsource) and the
 * font pairs offered in Appearance. Nothing is loaded from Google Fonts: the
 *
 * @font-face rules point at our own files, split into latin and latin-ext
 * (Turkish ğ, ş, İ...) so a page only downloads the subset it uses.
 */
final class Fonts
{
    public const LATIN = 'U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD';

    public const LATIN_EXT = 'U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF';

    public const SANS = 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif';

    public const SERIF = 'ui-serif, Georgia, Cambria, "Times New Roman", serif';

    /** Book-like serifs every system already has (macOS, iOS, Windows, Android): a classic look with no download. */
    public const CLASSIC_SERIF = '"Iowan Old Style", "Palatino Linotype", Palatino, "Book Antiqua", Georgia, serif';

    /** @var array<string, array{name: ?string, file: ?string, weight: string, fallback: string}> Families without a file are system fonts. */
    public const FAMILIES = [
        'system-serif' => ['name' => null, 'file' => null, 'weight' => '400 700', 'fallback' => self::CLASSIC_SERIF],
        'inter' => ['name' => 'Inter Variable', 'file' => 'inter', 'weight' => '100 900', 'fallback' => self::SANS],
        'jakarta' => ['name' => 'Plus Jakarta Sans Variable', 'file' => 'plus-jakarta-sans', 'weight' => '200 800', 'fallback' => self::SANS],
        'grotesk' => ['name' => 'Space Grotesk Variable', 'file' => 'space-grotesk', 'weight' => '300 700', 'fallback' => self::SANS],
        'fraunces' => ['name' => 'Fraunces Variable', 'file' => 'fraunces', 'weight' => '100 900', 'fallback' => self::SERIF],
        'newsreader' => ['name' => 'Newsreader Variable', 'file' => 'newsreader', 'weight' => '200 800', 'fallback' => self::SERIF],
        'source-serif' => ['name' => 'Source Serif 4 Variable', 'file' => 'source-serif-4', 'weight' => '200 900', 'fallback' => self::SERIF],
    ];

    /** Pair key => [heading family, body family]; null = system font stack. */
    public const PAIRS = [
        'system' => [null, null],
        'inter' => ['inter', 'inter'],
        'jakarta' => ['jakarta', 'inter'],
        'grotesk' => ['grotesk', 'inter'],
        'fraunces' => ['fraunces', 'inter'],
        'editorial' => ['newsreader', 'source-serif'],
        'classic' => ['system-serif', null],
    ];

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(array_keys(self::PAIRS))->mapWithKeys(fn ($key) => [$key => __("larawebkit-themes.fonts.$key")])->all();
    }

    /** @return array{heading: ?string, body: ?string} */
    public static function pair(string $key): array
    {
        [$heading, $body] = self::PAIRS[$key] ?? self::PAIRS['system'];

        return ['heading' => $heading, 'body' => $body];
    }

    public static function stack(?string $family, string $fallback = self::SANS): string
    {
        if ($family === null || ! isset(self::FAMILIES[$family])) {
            return $fallback;
        }

        $f = self::FAMILIES[$family];

        return $f['name'] === null ? $f['fallback'] : '"'.$f['name'].'", '.$f['fallback'];
    }

    /** @return list<string> the families of a pair that are font files (not system fonts) */
    private static function downloaded(string $pairKey): array
    {
        return array_values(array_filter(array_unique(array_filter(self::pair($pairKey))), fn ($f) => (self::FAMILIES[$f]['file'] ?? null) !== null));
    }

    /** @font-face rules for the families of a pair. */
    public static function faceCss(string $pairKey): string
    {
        $css = '';
        foreach (self::downloaded($pairKey) as $family) {
            $f = self::FAMILIES[$family];
            foreach (['latin-ext' => self::LATIN_EXT, 'latin' => self::LATIN] as $subset => $range) {
                $url = asset("fonts/lwk/{$f['file']}-$subset.woff2");
                $css .= "@font-face{font-family:\"{$f['name']}\";font-style:normal;font-display:swap;font-weight:{$f['weight']};src:url($url) format(\"woff2\");unicode-range:$range}";
            }
        }

        return $css;
    }

    /** Preload the latin subset of each family so text does not reflow late. */
    public static function preloads(string $pairKey): HtmlString
    {
        $tags = '';
        foreach (self::downloaded($pairKey) as $family) {
            $url = asset('fonts/lwk/'.self::FAMILIES[$family]['file'].'-latin.woff2');
            $tags .= '<link rel="preload" href="'.e($url).'" as="font" type="font/woff2" crossorigin>';
        }

        return new HtmlString($tags);
    }
}

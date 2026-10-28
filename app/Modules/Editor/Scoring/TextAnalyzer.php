<?php

namespace App\Modules\Editor\Scoring;

/**
 * Turns editor HTML into words, sentences and structural facts.
 *
 * The algorithm is mirrored line by line in resources/js/larawebkit/scoring.js;
 * tests/fixtures/scoring/*.json is run against both to keep them identical.
 */
class TextAnalyzer
{
    public const WORD_PATTERN = "/[\\p{L}\\p{N}]+(?:['’\\-][\\p{L}\\p{N}]+)*/u";

    public readonly string $html;

    public readonly string $text;

    /** @var list<string> */
    public readonly array $words;

    /** @var list<string> */
    public readonly array $sentences;

    public function __construct(string $html)
    {
        $this->html = $html;
        $this->text = self::htmlToText($html);
        $this->words = self::words($this->text);
        $this->sentences = self::sentences($this->text);
    }

    public static function htmlToText(string $html): string
    {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? '';
        $text = preg_replace('/<\/(p|h[1-6]|li|blockquote|div|figcaption|tr|pre)>/i', "\n", $text) ?? '';
        $text = preg_replace('/<[^>]*>/', ' ', $text) ?? '';
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);
        $text = preg_replace('/[ \t]+/u', ' ', $text) ?? '';
        $text = preg_replace('/ *\n */u', "\n", $text) ?? '';

        return trim($text);
    }

    /** @return list<string> */
    public static function words(string $text): array
    {
        preg_match_all(self::WORD_PATTERN, $text, $m);

        return array_map(fn (string $w) => mb_strtolower(str_replace('’', "'", $w)), $m[0]);
    }

    /** @return list<string> */
    public static function sentences(string $text): array
    {
        $parts = preg_split('/(?<=[.!?…])\s+|\n+/u', $text) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '' && self::words($part) !== []) {
                $out[] = $part;
            }
        }

        return $out;
    }

    /** Space-padded, lower-cased word sequence used for whole-word phrase matching. */
    public static function normalize(string $text): string
    {
        return ' '.implode(' ', self::words($text)).' ';
    }

    public function wordCount(): int
    {
        return count($this->words);
    }

    public function normalized(): string
    {
        return ' '.implode(' ', $this->words).' ';
    }

    public function countPhrase(string $phrase): int
    {
        $needle = self::normalize($phrase);
        if (trim($needle) === '') {
            return 0;
        }

        return substr_count($this->normalized(), $needle);
    }

    public function containsPhrase(string $phrase): bool
    {
        return $this->countPhrase($phrase) > 0;
    }

    public function countTag(string $tag): int
    {
        return preg_match_all('/<'.$tag.'(\s[^>]*)?>/i', $this->html);
    }

    /** @return list<string> */
    public function headings(string $tag): array
    {
        preg_match_all('/<'.$tag.'(?:\s[^>]*)?>(.*?)<\/'.$tag.'>/is', $this->html, $m);

        return array_map(fn (string $h) => self::htmlToText($h), $m[1]);
    }

    public function firstParagraph(): string
    {
        if (preg_match('/<p(?:\s[^>]*)?>(.*?)<\/p>/is', $this->html, $m)) {
            return self::htmlToText($m[1]);
        }

        return $this->sentences[0] ?? '';
    }

    /** @return list<array{alt: string}> */
    public function images(): array
    {
        preg_match_all('/<img\s[^>]*>/i', $this->html, $m);

        return array_map(function (string $tag) {
            preg_match('/\salt\s*=\s*"([^"]*)"/i', $tag, $a);

            return ['alt' => trim(html_entity_decode($a[1] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'))];
        }, $m[0]);
    }

    /** @return list<string> */
    public function links(): array
    {
        preg_match_all('/<a\s[^>]*href\s*=\s*"([^"]*)"/i', $this->html, $m);

        return array_map(fn (string $h) => html_entity_decode($h, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $m[1]);
    }
}

<?php

namespace App\Modules\Content\Support;

/**
 * Small word-level diff (LCS) for the revision "compare" view. No external
 * dependency so it works on shared hosting without dev packages.
 */
class TextDiff
{
    /** @return list<array{type: string, text: string}> type: same|add|del */
    public static function words(string $old, string $new, int $maxTokens = 4000): array
    {
        $a = self::tokens($old);
        $b = self::tokens($new);
        if (count($a) > $maxTokens || count($b) > $maxTokens) {
            return [['type' => 'del', 'text' => $old], ['type' => 'add', 'text' => $new]];
        }

        $n = count($a);
        $m = count($b);
        $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $a[$i] === $b[$j] ? $lcs[$i + 1][$j + 1] + 1 : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $out = [];
        $i = $j = 0;
        while ($i < $n && $j < $m) {
            if ($a[$i] === $b[$j]) {
                self::push($out, 'same', $a[$i]);
                $i++;
                $j++;
            } elseif ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                self::push($out, 'del', $a[$i++]);
            } else {
                self::push($out, 'add', $b[$j++]);
            }
        }
        while ($i < $n) {
            self::push($out, 'del', $a[$i++]);
        }
        while ($j < $m) {
            self::push($out, 'add', $b[$j++]);
        }

        return $out;
    }

    public static function html(string $old, string $new): string
    {
        return implode('', array_map(fn ($part) => match ($part['type']) {
            'add' => '<ins class="lwk-diff-add">'.e($part['text']).'</ins>',
            'del' => '<del class="lwk-diff-del">'.e($part['text']).'</del>',
            default => e($part['text']),
        }, self::words($old, $new)));
    }

    /** @return list<string> */
    private static function tokens(string $text): array
    {
        return preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
    }

    private static function push(array &$out, string $type, string $text): void
    {
        $last = array_key_last($out);
        if ($last !== null && $out[$last]['type'] === $type) {
            $out[$last]['text'] .= $text;
        } else {
            $out[] = ['type' => $type, 'text' => $text];
        }
    }
}

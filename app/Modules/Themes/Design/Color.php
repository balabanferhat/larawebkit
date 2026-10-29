<?php

namespace App\Modules\Themes\Design;

/**
 * Small sRGB helper for design tokens: parse hex colours, mix them and keep
 * text colours at WCAG AA contrast against their background, so any brand
 * colour picked in the admin still produces an accessible theme.
 */
final class Color
{
    /** @param  array{0: int, 1: int, 2: int}  $rgb */
    private function __construct(public readonly array $rgb) {}

    public static function hex(string $hex): ?self
    {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (! preg_match('/^[0-9a-f]{6}$/i', $hex)) {
            return null;
        }

        return new self([hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))]);
    }

    public static function valid(?string $hex): bool
    {
        return $hex !== null && self::hex($hex) !== null;
    }

    public function toHex(): string
    {
        return sprintf('#%02x%02x%02x', ...$this->rgb);
    }

    /** Mix towards $other by $amount (0 = this colour, 1 = other). */
    public function mix(self $other, float $amount): self
    {
        $amount = max(0.0, min(1.0, $amount));

        return new self([
            (int) round($this->rgb[0] + ($other->rgb[0] - $this->rgb[0]) * $amount),
            (int) round($this->rgb[1] + ($other->rgb[1] - $this->rgb[1]) * $amount),
            (int) round($this->rgb[2] + ($other->rgb[2] - $this->rgb[2]) * $amount),
        ]);
    }

    /** WCAG relative luminance. */
    public function luminance(): float
    {
        $channel = function (int $value): float {
            $c = $value / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };

        return 0.2126 * $channel($this->rgb[0]) + 0.7152 * $channel($this->rgb[1]) + 0.0722 * $channel($this->rgb[2]);
    }

    public function contrast(self $other): float
    {
        $a = $this->luminance() + 0.05;
        $b = $other->luminance() + 0.05;

        return round(max($a, $b) / min($a, $b), 2);
    }

    /** White or near-black, whichever reads better on this colour. */
    public function onColor(): self
    {
        $white = self::hex('#ffffff');
        $ink = self::hex('#0b0d12');

        return $this->contrast($white) >= $this->contrast($ink) ? $white : $ink;
    }

    /**
     * This colour, darkened or lightened (towards black or white, whichever
     * the background needs) until it reaches $ratio against $background.
     */
    public function readableOn(self $background, float $ratio = 4.5): self
    {
        $target = $background->luminance() > 0.4 ? self::hex('#000000') : self::hex('#ffffff');
        $color = $this;
        for ($step = 0; $step <= 20 && $color->contrast($background) < $ratio; $step++) {
            $color = $this->mix($target, $step * 0.05);
        }

        return $color;
    }
}

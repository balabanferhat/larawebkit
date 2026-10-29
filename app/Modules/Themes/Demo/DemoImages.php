<?php

namespace App\Modules\Themes\Demo;

use App\Modules\Themes\Design\Color;
use GdImage;

/**
 * Placeholder artwork for demo content, drawn with GD: abstract gradients,
 * stylised portraits and product mock-ups in the theme's colours. Generated
 * locally, so the demo contains no third-party or copyrighted images. Drawn
 * at 2× and scaled down for smooth edges; the result goes through the normal
 * WebP pipeline like any upload.
 */
class DemoImages
{
    public function __construct(private float $scale = 1.0) {}

    /** @param  array{0: string, 1: string}  $palette  hex colours (brand, accent) */
    public function make(string $kind, int $width, int $height, array $palette, int $seed): string
    {
        $width = max(64, (int) round($width * $this->scale));
        $height = max(64, (int) round($height * $this->scale));
        mt_srand($seed);
        $ss = $width * $height > 2_500_000 ? 1 : 2;
        $w = $width * $ss;
        $h = $height * $ss;
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, true);

        $a = Color::hex($palette[0]) ?? Color::hex('#3b3fd8');
        $b = Color::hex($palette[1]) ?? Color::hex('#db2777');

        match ($kind) {
            'portrait' => $this->portrait($img, $w, $h, $a, $b),
            'device' => $this->device($img, $w, $h, $a, $b),
            'pattern' => $this->pattern($img, $w, $h, $a, $b),
            'icon' => $this->icon($img, $w, $h, $a, $b),
            default => $this->abstract($img, $w, $h, $a, $b),
        };

        if ($ss > 1) {
            $scaled = imagescale($img, $width, $height, IMG_BICUBIC);
            unset($img);
            $img = $scaled;
        }
        ob_start();
        imagejpeg($img, null, 88);
        unset($img);

        return (string) ob_get_clean();
    }

    private function abstract(GdImage $img, int $w, int $h, Color $a, Color $b): void
    {
        $dark = mt_rand(0, 2) === 0;
        $from = $dark ? $a->mix(Color::hex('#0b0d12'), 0.55) : $a->mix(Color::hex('#ffffff'), 0.15);
        $to = $dark ? $b->mix(Color::hex('#0b0d12'), 0.35) : $b->mix(Color::hex('#ffffff'), 0.35);
        $this->gradient($img, $w, $h, $from, $to, mt_rand(0, 1) === 1);

        for ($i = 0; $i < 7; $i++) {
            $c = [$a, $b, Color::hex('#ffffff')][mt_rand(0, 2)];
            $r = (int) (min($w, $h) * mt_rand(18, 60) / 100);
            $this->circle($img, mt_rand(-$r / 2, $w), mt_rand(-$r / 2, $h), $r, $c, mt_rand(70, 105));
        }
        // A few crisp geometric accents.
        for ($i = 0; $i < 3; $i++) {
            $c = $this->alloc($img, Color::hex('#ffffff'), 95);
            $x = mt_rand(0, $w);
            $y = mt_rand(0, $h);
            $s = (int) (min($w, $h) * mt_rand(6, 14) / 100);
            imagesetthickness($img, max(2, (int) ($s / 14)));
            mt_rand(0, 1) ? imagerectangle($img, $x, $y, $x + $s, $y + $s, $c) : imagearc($img, $x, $y, $s * 2, $s * 2, 0, 360, $c);
        }
        $this->grain($img, $w, $h);
    }

    private function portrait(GdImage $img, int $w, int $h, Color $a, Color $b): void
    {
        $this->gradient($img, $w, $h, $a->mix(Color::hex('#ffffff'), 0.55), $b->mix(Color::hex('#ffffff'), 0.4), true);
        $this->circle($img, (int) ($w * 0.8), (int) ($h * 0.15), (int) ($w * 0.45), Color::hex('#ffffff'), 90);
        $skin = [Color::hex('#f1c7a8'), Color::hex('#d9a27e'), Color::hex('#b97a56'), Color::hex('#8d5a3b'), Color::hex('#f5d5bf')][mt_rand(0, 4)];
        $hair = [Color::hex('#2b1d14'), Color::hex('#6b4226'), Color::hex('#141414'), Color::hex('#a8743a'), Color::hex('#3d3d3d')][mt_rand(0, 4)];
        $shirt = $a->mix(Color::hex('#0b0d12'), mt_rand(10, 45) / 100);
        $cx = (int) ($w / 2);
        $head = (int) (min($w, $h) * 0.2);
        $headY = (int) ($h * 0.42);
        // Shoulders
        imagefilledellipse($img, $cx, (int) ($h * 1.02), (int) ($w * 0.95), (int) ($h * 0.62), $this->alloc($img, $shirt));
        // Neck
        imagefilledrectangle($img, $cx - (int) ($head * 0.38), $headY, $cx + (int) ($head * 0.38), (int) ($h * 0.74), $this->alloc($img, $skin->mix(Color::hex('#000000'), 0.08)));
        // Hair behind, head, hair on top
        imagefilledellipse($img, $cx, $headY - (int) ($head * 0.1), (int) ($head * 2.3), (int) ($head * 2.45), $this->alloc($img, $hair));
        imagefilledellipse($img, $cx, $headY + (int) ($head * 0.08), (int) ($head * 1.85), (int) ($head * 2.15), $this->alloc($img, $skin));
        imagefilledarc($img, $cx, $headY - (int) ($head * 0.25), (int) ($head * 2.05), (int) ($head * 1.75), 180, 360, $this->alloc($img, $hair), IMG_ARC_PIE);
        $this->grain($img, $w, $h);
    }

    /** A product/app mock-up: a browser window with a chart and cards. */
    private function device(GdImage $img, int $w, int $h, Color $a, Color $b): void
    {
        $this->gradient($img, $w, $h, $a->mix(Color::hex('#ffffff'), 0.82), $b->mix(Color::hex('#ffffff'), 0.85), false);
        $this->circle($img, (int) ($w * 0.9), (int) ($h * 0.1), (int) ($w * 0.35), $a, 100);
        $pad = (int) ($w * 0.07);
        [$x1, $y1, $x2, $y2] = [$pad, (int) ($pad * 0.9), $w - $pad, $h - (int) ($pad * 0.6)];
        $this->roundRect($img, $x1 + 10, $y1 + 18, $x2 + 10, $y2 + 18, (int) ($w * 0.02), $this->alloc($img, Color::hex('#0b0d12'), 112));
        $this->roundRect($img, $x1, $y1, $x2, $y2, (int) ($w * 0.02), $this->alloc($img, Color::hex('#ffffff')));
        $bar = (int) (($y2 - $y1) * 0.08);
        imagefilledrectangle($img, $x1 + (int) ($w * 0.02), $y1 + $bar, $x2 - (int) ($w * 0.02), $y1 + $bar + 2, $this->alloc($img, Color::hex('#e5e7eb')));
        foreach ([0, 1, 2] as $i) {
            imagefilledellipse($img, $x1 + (int) ($w * 0.03) + $i * (int) ($w * 0.022), $y1 + (int) ($bar / 2), (int) ($w * 0.012), (int) ($w * 0.012), $this->alloc($img, [Color::hex('#f87171'), Color::hex('#fbbf24'), Color::hex('#34d399')][$i]));
        }
        // Sidebar
        $sx = $x1 + (int) ($w * 0.03);
        $top = $y1 + $bar + (int) ($h * 0.05);
        for ($i = 0; $i < 6; $i++) {
            $this->roundRect($img, $sx, $top + $i * (int) ($h * 0.07), $sx + (int) ($w * 0.14), $top + $i * (int) ($h * 0.07) + (int) ($h * 0.03), 6, $this->alloc($img, $i === 1 ? $a : Color::hex('#eef0f4')));
        }
        // Stat cards
        $cx = $sx + (int) ($w * 0.18);
        $cw = (int) (($x2 - $cx - (int) ($w * 0.03)) / 3) - 12;
        for ($i = 0; $i < 3; $i++) {
            $left = $cx + $i * ($cw + 12);
            $this->roundRect($img, $left, $top, $left + $cw, $top + (int) ($h * 0.15), 10, $this->alloc($img, Color::hex('#f6f7f9')));
            $this->roundRect($img, $left + 16, $top + 18, $left + (int) ($cw * 0.55), $top + 34, 6, $this->alloc($img, $i === 0 ? $b : $a, 30));
            $this->roundRect($img, $left + 16, $top + (int) ($h * 0.15) - 40, $left + (int) ($cw * 0.8), $top + (int) ($h * 0.15) - 22, 6, $this->alloc($img, Color::hex('#d8dde5')));
        }
        // Chart
        $chartTop = $top + (int) ($h * 0.2);
        $chartBottom = $y2 - (int) ($h * 0.06);
        $this->roundRect($img, $cx, $chartTop, $x2 - (int) ($w * 0.03), $chartBottom, 10, $this->alloc($img, Color::hex('#f6f7f9')));
        $points = [];
        $n = 9;
        $span = ($x2 - (int) ($w * 0.03) - $cx - 40) / ($n - 1);
        $level = 0.7;
        for ($i = 0; $i < $n; $i++) {
            $level = max(0.15, min(0.85, $level - mt_rand(-8, 16) / 100));
            $points[] = (int) ($cx + 20 + $i * $span);
            $points[] = (int) ($chartTop + 20 + ($chartBottom - $chartTop - 40) * $level);
        }
        $area = [...$points, (int) ($cx + 20 + ($n - 1) * $span), $chartBottom - 20, $cx + 20, $chartBottom - 20];
        imagefilledpolygon($img, $area, $this->alloc($img, $a, 95));
        imagesetthickness($img, max(3, (int) ($w / 260)));
        for ($i = 0; $i < count($points) - 2; $i += 2) {
            imageline($img, $points[$i], $points[$i + 1], $points[$i + 2], $points[$i + 3], $this->alloc($img, $a));
        }
    }

    private function pattern(GdImage $img, int $w, int $h, Color $a, Color $b): void
    {
        $this->gradient($img, $w, $h, $a, $b->mix($a, 0.4), true);
        $size = (int) (min($w, $h) / mt_rand(5, 8));
        for ($y = 0; $y < $h + $size; $y += $size) {
            for ($x = 0; $x < $w + $size; $x += $size) {
                $c = $this->alloc($img, Color::hex('#ffffff'), mt_rand(88, 120));
                match (mt_rand(0, 3)) {
                    0 => imagefilledellipse($img, $x, $y, (int) ($size * 0.6), (int) ($size * 0.6), $c),
                    1 => imagefilledarc($img, $x, $y, $size * 2, $size * 2, 0, 90, $c, IMG_ARC_PIE),
                    2 => imagefilledrectangle($img, $x, $y, $x + (int) ($size / 2), $y + (int) ($size / 2), $c),
                    default => null,
                };
            }
        }
    }

    /** A square mark for logos/icons: a geometric symbol on a soft square. */
    private function icon(GdImage $img, int $w, int $h, Color $a, Color $b): void
    {
        $this->gradient($img, $w, $h, $a, $b, true);
        $c = $this->alloc($img, Color::hex('#ffffff'));
        $m = (int) ($w * 0.28);
        match (mt_rand(0, 2)) {
            0 => imagefilledellipse($img, (int) ($w / 2), (int) ($h / 2), $w - 2 * $m, $h - 2 * $m, $c),
            1 => imagefilledpolygon($img, [(int) ($w / 2), $m, $w - $m, $h - $m, $m, $h - $m], $c),
            default => $this->roundRect($img, $m, $m, $w - $m, $h - $m, (int) ($w * 0.08), $c),
        };
    }

    private function gradient(GdImage $img, int $w, int $h, Color $from, Color $to, bool $diagonal): void
    {
        $steps = $diagonal ? $w + $h : $h;
        for ($i = 0; $i < $steps; $i += 2) {
            $c = $this->alloc($img, $from->mix($to, $i / max(1, $steps)));
            if ($diagonal) {
                imageline($img, $i, 0, 0, $i, $c);
                imageline($img, $i + 1, 0, 0, $i + 1, $c);
            } else {
                imagefilledrectangle($img, 0, $i, $w, $i + 1, $c);
            }
        }
    }

    private function circle(GdImage $img, int $x, int $y, int $r, Color $c, int $alpha = 0): void
    {
        imagefilledellipse($img, $x, $y, $r * 2, $r * 2, $this->alloc($img, $c, $alpha));
    }

    private function roundRect(GdImage $img, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
    {
        imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
            imagefilledellipse($img, $cx, $cy, $r * 2, $r * 2, $color);
        }
    }

    /** Subtle noise so gradients do not band. */
    private function grain(GdImage $img, int $w, int $h): void
    {
        $dots = (int) ($w * $h / 900);
        for ($i = 0; $i < $dots; $i++) {
            imagesetpixel($img, mt_rand(0, $w - 1), mt_rand(0, $h - 1), imagecolorallocatealpha($img, 255, 255, 255, 118));
        }
    }

    private function alloc(GdImage $img, Color $c, int $alpha = 0): int
    {
        return imagecolorallocatealpha($img, $c->rgb[0], $c->rgb[1], $c->rgb[2], max(0, min(127, $alpha)));
    }
}

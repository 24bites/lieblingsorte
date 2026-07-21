<?php

namespace App\Support;

/**
 * Generates deterministic, editorial-style placeholder photography (gradients,
 * layered silhouettes) so the seeded catalog ships with real local image files
 * instead of broken hotlinks or empty gray boxes. See README "Bildverwaltung"
 * for how to swap these for real photography later.
 */
class ImageGenerator
{
    public static function generate(string $absolutePath, string $seed, string $motif, array $palette, int $width = 1600, int $height = 900): void
    {
        $dir = dirname($absolutePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        mt_srand(crc32($seed));

        $image = imagecreatetruecolor($width, $height);
        imagesavealpha($image, true);

        self::drawSkyGradient($image, $width, $height, $palette['sky_top'], $palette['sky_bottom']);
        self::drawSun($image, $width, $height, $palette['accent']);

        match ($motif) {
            'lake' => self::drawLake($image, $width, $height, $palette),
            'waterfall' => self::drawWaterfall($image, $width, $height, $palette),
            'town' => self::drawTown($image, $width, $height, $palette),
            'castle' => self::drawCastle($image, $width, $height, $palette),
            'garden' => self::drawGarden($image, $width, $height, $palette),
            'indoor' => self::drawIndoor($image, $width, $height, $palette),
            'canyon' => self::drawCanyon($image, $width, $height, $palette),
            default => self::drawMountains($image, $width, $height, $palette),
        };

        self::drawGrain($image, $width, $height);

        imagejpeg($image, $absolutePath, 88);
        imagedestroy($image);

        mt_srand();
    }

    private static function hex(array|string $hex): array
    {
        $hex = ltrim(is_array($hex) ? $hex[0] : $hex, '#');
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private static function drawSkyGradient($image, int $w, int $h, string $top, string $bottom): void
    {
        [$r1, $g1, $b1] = self::hex($top);
        [$r2, $g2, $b2] = self::hex($bottom);

        for ($y = 0; $y < $h; $y++) {
            $ratio = $y / $h;
            $r = (int) ($r1 + ($r2 - $r1) * $ratio);
            $g = (int) ($g1 + ($g2 - $g1) * $ratio);
            $b = (int) ($b1 + ($b2 - $b1) * $ratio);
            $color = imagecolorallocate($image, $r, $g, $b);
            imageline($image, 0, $y, $w, $y, $color);
        }
    }

    private static function drawSun($image, int $w, int $h, string $accent): void
    {
        [$r, $g, $b] = self::hex($accent);
        $cx = (int) ($w * (0.2 + mt_rand(0, 60) / 100));
        $cy = (int) ($h * (0.18 + mt_rand(0, 10) / 100));

        for ($radius = 140; $radius > 0; $radius -= 4) {
            $alpha = (int) (110 * ($radius / 140));
            $color = imagecolorallocatealpha($image, $r, $g, $b, 127 - (int) ((127 - $alpha) * ($radius / 140)));
            $color = imagecolorallocatealpha($image, $r, $g, $b, max(70, 127 - $radius / 2));
            imagefilledellipse($image, $cx, $cy, $radius, $radius, $color);
        }
    }

    private static function ridge(int $w, int $baseY, int $amplitude, int $segments = 8): array
    {
        $points = [0, $baseY];
        $step = $w / $segments;
        for ($i = 0; $i <= $segments; $i++) {
            $x = (int) ($i * $step);
            $y = $baseY - mt_rand(0, $amplitude) + mt_rand(0, (int) ($amplitude / 2));
            $points[] = $x;
            $points[] = $y;
        }
        $points[] = $w;
        $points[] = $baseY;
        $points[] = $w;
        $points[] = $baseY + 400;
        $points[] = 0;
        $points[] = $baseY + 400;

        return $points;
    }

    private static function drawMountains($image, int $w, int $h, array $palette): void
    {
        $layers = $palette['layers'];
        $count = count($layers);
        foreach ($layers as $i => $hexColor) {
            [$r, $g, $b] = self::hex($hexColor);
            $color = imagecolorallocate($image, $r, $g, $b);
            $baseY = (int) ($h * (0.45 + ($i / $count) * 0.42));
            $amplitude = (int) ($h * 0.22 * (1 - $i / ($count + 1)));
            $points = self::ridge($w, $baseY, $amplitude, 7 + $i);
            imagefilledpolygon($image, $points, $color);
        }
    }

    private static function drawLake($image, int $w, int $h, array $palette): void
    {
        $horizon = (int) ($h * 0.6);
        self::drawMountains($image, $w, (int) ($horizon * 1.55), $palette);

        [$r, $g, $b] = self::hex($palette['water']);
        $water = imagecolorallocate($image, $r, $g, $b);
        imagefilledrectangle($image, 0, $horizon, $w, $h, $water);

        for ($y = $horizon; $y < $h; $y += 6) {
            $alpha = 60 + (int) (($y - $horizon) / ($h - $horizon) * 50);
            $shade = imagecolorallocatealpha($image, max(0, $r - 20), max(0, $g - 15), max(0, $b - 10), $alpha);
            imageline($image, 0, $y, $w, $y, $shade);
        }

        for ($i = 0; $i < 40; $i++) {
            $x = mt_rand(0, $w);
            $y = mt_rand($horizon + 10, $h - 10);
            $len = mt_rand(20, 90);
            $hl = imagecolorallocatealpha($image, 255, 255, 255, 112);
            imageline($image, $x, $y, $x + $len, $y, $hl);
        }
    }

    private static function drawWaterfall($image, int $w, int $h, array $palette): void
    {
        self::drawMountains($image, $w, $h, $palette);

        $x = (int) ($w * 0.46);
        $width = (int) ($w * 0.09);
        for ($i = 0; $i < $width; $i++) {
            $alpha = 20 + (int) (abs($width / 2 - $i) * 1.4);
            $c = imagecolorallocatealpha($image, 245, 248, 250, max(10, $alpha));
            imageline($image, $x + $i, (int) ($h * 0.28), $x + $i + mt_rand(-4, 4), $h, $c);
        }

        [$r, $g, $b] = self::hex($palette['water']);
        $mist = imagecolorallocatealpha($image, 255, 255, 255, 95);
        imagefilledellipse($image, $x + (int) ($width / 2), (int) ($h * 0.92), $width * 3, 80, $mist);
    }

    private static function drawCanyon($image, int $w, int $h, array $palette): void
    {
        $layers = $palette['layers'];
        foreach ($layers as $i => $hexColor) {
            [$r, $g, $b] = self::hex($hexColor);
            $color = imagecolorallocate($image, $r, $g, $b);
            $inset = (int) ($w * (0.02 + $i * 0.09));
            $points = [
                $inset, $h,
                $inset + mt_rand(10, 40), (int) ($h * 0.25),
                $inset + mt_rand(60, 120), (int) ($h * 0.2),
                $inset + mt_rand(20, 60), $h,
            ];
            imagefilledpolygon($image, $points, $color);

            $points2 = [
                $w - $inset, $h,
                $w - $inset - mt_rand(10, 40), (int) ($h * 0.25),
                $w - $inset - mt_rand(60, 120), (int) ($h * 0.2),
                $w - $inset - mt_rand(20, 60), $h,
            ];
            imagefilledpolygon($image, $points2, $color);
        }

        [$r, $g, $b] = self::hex($palette['water']);
        $water = imagecolorallocatealpha($image, $r, $g, $b, 30);
        imagefilledrectangle($image, (int) ($w * 0.4), (int) ($h * 0.7), (int) ($w * 0.6), $h, $water);
    }

    private static function drawTown($image, int $w, int $h, array $palette): void
    {
        self::drawMountains($image, $w, (int) ($h * 0.85), $palette);

        $baseY = (int) ($h * 0.82);
        [$r, $g, $b] = self::hex($palette['layers'][array_key_last($palette['layers'])]);
        $x = 0;
        while ($x < $w) {
            $bw = mt_rand(90, 160);
            $bh = mt_rand((int) ($h * 0.08), (int) ($h * 0.15));
            $shade = mt_rand(-12, 12);
            $color = imagecolorallocate($image, max(0, $r + $shade), max(0, $g + $shade), max(0, $b + $shade));
            imagefilledrectangle($image, $x, $baseY - $bh, $x + $bw, $h, $color);

            $roof = [$x - 6, $baseY - $bh, $x + $bw / 2, $baseY - $bh - mt_rand(28, 48), $x + $bw + 6, $baseY - $bh];
            imagefilledpolygon($image, $roof, $color);

            [$wr, $wg, $wb] = self::hex($palette['accent']);
            for ($wy = $baseY - $bh + 16; $wy < $h - 18; $wy += 26) {
                for ($wx = $x + 14; $wx < $x + $bw - 14; $wx += 26) {
                    if (mt_rand(0, 100) < 45) {
                        $win = imagecolorallocatealpha($image, $wr, $wg, $wb, 65);
                        imagefilledrectangle($image, $wx, $wy, $wx + 9, $wy + 13, $win);
                    }
                }
            }
            $x += $bw + mt_rand(2, 10);
        }
    }

    private static function drawCastle($image, int $w, int $h, array $palette): void
    {
        self::drawMountains($image, $w, $h, $palette);

        [$r, $g, $b] = self::hex($palette['layers'][array_key_last($palette['layers'])]);
        $color = imagecolorallocate($image, $r, $g, $b);

        $cx = (int) ($w * 0.5);
        $baseY = (int) ($h * 0.78);
        $towerW = 70;
        $towerH = 200;

        imagefilledrectangle($image, $cx - $towerW / 2, $baseY - $towerH, $cx + $towerW / 2, $baseY, $color);
        $roof = [
            $cx - $towerW / 2 - 10, $baseY - $towerH,
            $cx, $baseY - $towerH - 90,
            $cx + $towerW / 2 + 10, $baseY - $towerH,
        ];
        imagefilledpolygon($image, $roof, $color);

        foreach ([-1, 1] as $side) {
            $sx = $cx + $side * 110;
            imagefilledrectangle($image, $sx - 25, $baseY - 120, $sx + 25, $baseY, $color);
            $sroof = [$sx - 30, $baseY - 120, $sx, $baseY - 175, $sx + 30, $baseY - 120];
            imagefilledpolygon($image, $sroof, $color);
        }
    }

    private static function drawGarden($image, int $w, int $h, array $palette): void
    {
        self::drawMountains($image, $w, (int) ($h * 0.7), $palette);

        $baseY = (int) ($h * 0.72);
        [$r, $g, $b] = self::hex($palette['layers'][0]);
        $hedge = imagecolorallocate($image, $r, $g, $b);
        imagefilledrectangle($image, 0, $baseY, $w, $h, $hedge);

        $blossom = [$palette['accent'], '#d98fb0', '#f2e2b1'];
        for ($i = 0; $i < 260; $i++) {
            $c = self::hex($blossom[array_rand($blossom)]);
            $color = imagecolorallocatealpha($image, $c[0], $c[1], $c[2], mt_rand(20, 70));
            $x = mt_rand(0, $w);
            $y = mt_rand($baseY + 10, $h - 10);
            $size = mt_rand(4, 10);
            imagefilledellipse($image, $x, $y, $size, $size, $color);
        }
    }

    private static function drawIndoor($image, int $w, int $h, array $palette): void
    {
        [$r, $g, $b] = self::hex($palette['accent']);
        for ($i = 0; $i < 5; $i++) {
            $radius = 900 - $i * 150;
            $alpha = 70 + $i * 8;
            $color = imagecolorallocatealpha($image, $r, $g, $b, min(120, $alpha));
            imagefilledellipse($image, (int) ($w * 0.5), (int) ($h * 0.55), $radius, $radius, $color);
        }

        [$lr, $lg, $lb] = self::hex($palette['layers'][0]);
        for ($y = (int) ($h * 0.75); $y < $h; $y += 30) {
            $shelf = imagecolorallocatealpha($image, $lr, $lg, $lb, 40);
            imagefilledrectangle($image, 0, $y, $w, $y + 6, $shelf);
        }
    }

    private static function drawGrain($image, int $w, int $h): void
    {
        for ($i = 0; $i < 1800; $i++) {
            $x = mt_rand(0, $w - 1);
            $y = mt_rand(0, $h - 1);
            $shade = mt_rand(0, 1) ? 255 : 0;
            $color = imagecolorallocatealpha($image, $shade, $shade, $shade, 122);
            imagesetpixel($image, $x, $y, $color);
        }
    }
}

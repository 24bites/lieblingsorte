<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Builds a 1000x1500 (2:3) Pinterest pin image from an existing cover photo:
 * cover-crops it to the pin aspect ratio, adds a bottom gradient for
 * legibility, and burns in a headline/subline text overlay via GD. Pinterest
 * requires the overlay text directly on the image (not just in the
 * description), and image-generation models render text unreliably, so this
 * is done with plain GD + bundled TTF fonts instead.
 */
class PinImageComposer
{
    private const WIDTH = 1000;

    private const HEIGHT = 1500;

    private const PADDING = 64;

    private const QUALITY = 88;

    private const HEADLINE_FONT = 'resources/fonts/Fraunces-Bold.ttf';

    private const SUBLINE_FONT = 'resources/fonts/Inter-Bold.ttf';

    public static function compose(string $sourceRelativePath, string $headline, ?string $subline, string $directory, string $baseName): ?string
    {
        $disk = Storage::disk('public');
        $absoluteSource = $disk->path($sourceRelativePath);

        if (! file_exists($absoluteSource)) {
            return null;
        }

        $info = @getimagesize($absoluteSource);
        if (! $info) {
            return null;
        }

        $source = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($absoluteSource),
            'image/png' => @imagecreatefrompng($absoluteSource),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absoluteSource) : null,
            default => null,
        };

        if (! $source) {
            return null;
        }

        self::ensureMemoryHeadroom();

        $canvas = self::coverCrop($source, self::WIDTH, self::HEIGHT);
        imagedestroy($source);

        self::applyBottomGradient($canvas);
        self::drawText($canvas, $headline, $subline);

        $filename = Str::slug($baseName).'-'.now()->format('Ymd-His').'-'.Str::random(4).'.jpg';
        $relativePath = "{$directory}/{$filename}";

        $disk->makeDirectory($directory);
        imagejpeg($canvas, $disk->path($relativePath), self::QUALITY);
        imagedestroy($canvas);

        return $relativePath;
    }

    private static function coverCrop($source, int $targetWidth, int $targetHeight)
    {
        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);
        $targetRatio = $targetWidth / $targetHeight;
        $srcRatio = $srcWidth / $srcHeight;

        if ($srcRatio > $targetRatio) {
            $cropHeight = $srcHeight;
            $cropWidth = (int) round($srcHeight * $targetRatio);
            $srcX = (int) (($srcWidth - $cropWidth) / 2);
            $srcY = 0;
        } else {
            $cropWidth = $srcWidth;
            $cropHeight = (int) round($srcWidth / $targetRatio);
            $srcX = 0;
            $srcY = (int) (($srcHeight - $cropHeight) / 2);
        }

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($canvas, $source, 0, 0, $srcX, $srcY, $targetWidth, $targetHeight, $cropWidth, $cropHeight);

        return $canvas;
    }

    /**
     * Fades from fully transparent at ~40% down the image to a dark, mostly
     * opaque band at the bottom, so white overlay text stays legible
     * regardless of what the underlying photo looks like there.
     */
    private static function applyBottomGradient($canvas): void
    {
        imagealphablending($canvas, true);

        $gradientStart = (int) round(self::HEIGHT * 0.4);

        for ($y = $gradientStart; $y < self::HEIGHT; $y++) {
            $progress = ($y - $gradientStart) / (self::HEIGHT - $gradientStart);
            $alpha = 127 - (int) round($progress * 97);
            $color = imagecolorallocatealpha($canvas, 10, 20, 15, max(0, $alpha));
            imagefilledrectangle($canvas, 0, $y, self::WIDTH - 1, $y, $color);
        }
    }

    private static function drawText($canvas, string $headline, ?string $subline): void
    {
        $fontsPath = base_path();
        $headlineFont = $fontsPath.'/'.self::HEADLINE_FONT;
        $sublineFont = $fontsPath.'/'.self::SUBLINE_FONT;

        $maxTextWidth = self::WIDTH - (2 * self::PADDING);

        [$headlineLines, $headlineSize] = self::fitText($headlineFont, $headline, $maxTextWidth, 72, 40, 3);
        $headlineLineHeight = (int) round($headlineSize * 1.28);

        $sublineLines = [];
        $sublineSize = 32;
        $sublineLineHeight = (int) round($sublineSize * 1.3);
        if (filled($subline)) {
            [$sublineLines, $sublineSize] = self::fitText($sublineFont, $subline, $maxTextWidth, 34, 24, 2);
            $sublineLineHeight = (int) round($sublineSize * 1.3);
        }

        $gap = filled($subline) ? 24 : 0;
        $blockHeight = (count($headlineLines) * $headlineLineHeight) + $gap + (count($sublineLines) * $sublineLineHeight);
        $y = self::HEIGHT - self::PADDING - $blockHeight;

        $white = imagecolorallocate($canvas, 255, 255, 255);
        $shadow = imagecolorallocatealpha($canvas, 0, 0, 0, 55);

        foreach ($headlineLines as $line) {
            $y += $headlineLineHeight;
            self::drawCenteredLine($canvas, $headlineFont, $headlineSize, $line, $y, $white, $shadow);
        }

        $y += $gap;

        $sand = imagecolorallocate($canvas, 245, 238, 224);

        foreach ($sublineLines as $line) {
            $y += $sublineLineHeight;
            self::drawCenteredLine($canvas, $sublineFont, $sublineSize, $line, $y, $sand, $shadow);
        }
    }

    private static function drawCenteredLine($canvas, string $font, float $size, string $line, int $baselineY, int $color, int $shadowColor): void
    {
        $bbox = imagettfbbox($size, 0, $font, $line);
        $textWidth = $bbox[2] - $bbox[0];
        $x = (int) round((self::WIDTH - $textWidth) / 2) - $bbox[0];

        imagettftext($canvas, $size, 0, $x + 2, $baselineY + 2, $shadowColor, $font, $line);
        imagettftext($canvas, $size, 0, $x, $baselineY, $color, $font, $line);
    }

    /**
     * Shrinks the font size until the text wraps into at most $maxLines
     * lines that each fit within $maxWidth, so long titles never overflow
     * the pin.
     */
    private static function fitText(string $font, string $text, int $maxWidth, int $startSize, int $minSize, int $maxLines): array
    {
        for ($size = $startSize; $size >= $minSize; $size -= 4) {
            $lines = self::wrapText($font, $text, $size, $maxWidth);
            if (count($lines) <= $maxLines) {
                return [$lines, $size];
            }
        }

        return [array_slice(self::wrapText($font, $text, $minSize, $maxWidth), 0, $maxLines), $minSize];
    }

    private static function wrapText(string $font, string $text, float $size, int $maxWidth): array
    {
        $words = preg_split('/\s+/', trim($text));
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $testLine = trim($currentLine.' '.$word);
            $bbox = imagettfbbox($size, 0, $font, $testLine);
            $width = $bbox[2] - $bbox[0];

            if ($width <= $maxWidth || $currentLine === '') {
                $currentLine = $testLine;
            } else {
                $lines[] = $currentLine;
                $currentLine = $word;
            }
        }

        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    private static function ensureMemoryHeadroom(): void
    {
        $current = self::iniToBytes((string) ini_get('memory_limit'));

        if ($current !== -1 && $current < 256 * 1024 * 1024) {
            ini_set('memory_limit', '256M');
        }
    }

    private static function iniToBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return -1;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}

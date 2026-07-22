<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores admin-uploaded images with speaking filenames and attaches them as
 * Media records. A web-optimized sibling (downscaled, EXIF-rotated,
 * WebP/JPEG) is generated alongside the untouched original whenever GD
 * supports it on the host.
 */
class ImageUploadService
{
    private const MAX_DIMENSION = 2000;

    private const QUALITY = 82;

    public static function store(UploadedFile $file, string $directory, string $baseName): string
    {
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $filename = Str::slug($baseName).'-'.now()->format('Ymd-His').'-'.Str::random(4).'.'.$extension;
        $relativePath = "{$directory}/{$filename}";

        $file->storeAs($directory, $filename, 'public');

        return $relativePath;
    }

    public static function storeBinary(string $contents, string $directory, string $baseName, string $extension = 'png'): string
    {
        $filename = Str::slug($baseName).'-'.now()->format('Ymd-His').'-'.Str::random(4).'.'.$extension;
        $relativePath = "{$directory}/{$filename}";

        Storage::disk('public')->put($relativePath, $contents);

        return $relativePath;
    }

    public static function attach(Model $model, string $relativePath, string $altText, bool $isCover, int $sortOrder, string $source = 'upload'): void
    {
        $model->media()->create([
            'file_path' => $relativePath,
            'optimized_path' => self::optimize($relativePath),
            'alt_text' => $altText,
            'sort_order' => $sortOrder,
            'is_cover' => $isCover,
            'source' => $source,
        ]);
    }

    /**
     * Auto-rotates JPEGs per their EXIF orientation tag (iPhone photos
     * virtually always carry one), downscales to a sane web-display size,
     * and re-encodes as WebP - falling back to JPEG where GD lacks WebP
     * support. Writes a "-web.{ext}" sibling next to the untouched original
     * and returns its relative path, or null if optimization wasn't possible.
     */
    public static function optimize(string $relativePath): ?string
    {
        $disk = Storage::disk('public');
        $absolutePath = $disk->path($relativePath);

        if (! file_exists($absolutePath)) {
            return null;
        }

        $info = @getimagesize($absolutePath);
        if (! $info) {
            return null;
        }

        $image = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($absolutePath),
            'image/png' => @imagecreatefrompng($absolutePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : null,
            default => null,
        };

        if (! $image) {
            return null;
        }

        if ($info['mime'] === 'image/jpeg') {
            $image = self::autoRotate($image, $absolutePath);
        }

        self::ensureMemoryHeadroom();

        $image = self::resizeToFit($image, self::MAX_DIMENSION);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        if (function_exists('imagewebp')) {
            $optimizedRelative = preg_replace('/\.[^.]+$/', '-web.webp', $relativePath);
            imagewebp($image, $disk->path($optimizedRelative), self::QUALITY);
        } else {
            $optimizedRelative = preg_replace('/\.[^.]+$/', '-web.jpg', $relativePath);
            imagejpeg($image, $disk->path($optimizedRelative), self::QUALITY);
        }

        imagedestroy($image);

        return $optimizedRelative;
    }

    private static function autoRotate($image, string $absolutePath)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (@exif_read_data($absolutePath))['Orientation'] ?? 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if ($rotated !== $image) {
            imagedestroy($image);
        }

        return $rotated;
    }

    private static function resizeToFit($image, int $maxDimension)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longEdge = max($width, $height);

        if ($longEdge <= $maxDimension) {
            return $image;
        }

        $scale = $maxDimension / $longEdge;
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    /**
     * GD needs enough headroom to hold a decompressed multi-megapixel photo
     * in memory during resize; raise the limit for this request only if the
     * host's configured limit is lower (never lowers an existing higher one).
     */
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

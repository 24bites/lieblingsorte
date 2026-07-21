<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores admin-uploaded images with speaking filenames and attaches them as
 * Media records. WebP is generated only when GD supports it on the host.
 */
class ImageUploadService
{
    public static function store(UploadedFile $file, string $directory, string $baseName): string
    {
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $filename = Str::slug($baseName).'-'.now()->format('Ymd-His').'-'.Str::random(4).'.'.$extension;
        $relativePath = "{$directory}/{$filename}";

        $file->storeAs($directory, $filename, 'public');

        self::maybeGenerateWebp(storage_path("app/public/{$relativePath}"));

        return $relativePath;
    }

    public static function storeBinary(string $contents, string $directory, string $baseName, string $extension = 'png'): string
    {
        $filename = Str::slug($baseName).'-'.now()->format('Ymd-His').'-'.Str::random(4).'.'.$extension;
        $relativePath = "{$directory}/{$filename}";

        Storage::disk('public')->put($relativePath, $contents);

        self::maybeGenerateWebp(storage_path("app/public/{$relativePath}"));

        return $relativePath;
    }

    private static function maybeGenerateWebp(string $absolutePath): void
    {
        if (! function_exists('imagewebp') || ! file_exists($absolutePath)) {
            return;
        }

        $info = @getimagesize($absolutePath);
        if (! $info) {
            return;
        }

        $image = match ($info['mime']) {
            'image/jpeg' => imagecreatefromjpeg($absolutePath),
            'image/png' => imagecreatefrompng($absolutePath),
            default => null,
        };

        if ($image === null) {
            return;
        }

        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $absolutePath);
        imagewebp($image, $webpPath, 82);
        imagedestroy($image);
    }

    public static function attach(Model $model, string $relativePath, string $altText, bool $isCover, int $sortOrder): void
    {
        $model->media()->create([
            'file_path' => $relativePath,
            'alt_text' => $altText,
            'sort_order' => $sortOrder,
            'is_cover' => $isCover,
        ]);
    }
}

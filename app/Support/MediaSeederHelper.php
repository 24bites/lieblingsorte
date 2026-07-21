<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * Generates a placeholder editorial image and attaches it as a Media record
 * to a Region or TravelTip during seeding. See ImageGenerator for the
 * rendering logic and README "Bildverwaltung" for the replacement concept.
 */
class MediaSeederHelper
{
    public static function attach(Model $model, string $directory, string $seed, string $motif, array $palette, string $altText, bool $isCover, int $sortOrder = 0): void
    {
        $filename = $seed.'-'.($sortOrder + 1).'.jpg';
        $relativePath = "{$directory}/{$filename}";
        $absolutePath = storage_path("app/public/{$relativePath}");

        ImageGenerator::generate($absolutePath, $seed.'-'.$sortOrder, $motif, $palette);

        $model->media()->create([
            'file_path' => $relativePath,
            'alt_text' => $altText,
            'sort_order' => $sortOrder,
            'is_cover' => $isCover,
        ]);
    }
}

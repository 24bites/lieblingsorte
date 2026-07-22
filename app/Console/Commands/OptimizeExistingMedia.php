<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Support\ImageUploadService;
use Illuminate\Console\Command;

/**
 * One-off backfill for images stored before the web-optimization pipeline
 * existed: generates the missing "-web" WebP/JPEG sibling for every Media row
 * that doesn't have one yet. No API costs involved (pure local GD work), so
 * unlike the AI crons this isn't scheduled - just run once after deploying
 * the optimizer and again for any batch of newly imported media.
 */
class OptimizeExistingMedia extends Command
{
    protected $signature = 'images:optimize {--limit=0 : Maximum number of media rows to process (0 = all)}';

    protected $description = 'Generate the web-optimized image variant for existing media rows that are missing one';

    public function handle(): int
    {
        $query = Media::query()->whereNull('optimized_path')->orderBy('id');

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $candidates = $query->get();

        if ($candidates->isEmpty()) {
            $this->info('Keine Medien ohne optimierte Variante gefunden.');

            return self::SUCCESS;
        }

        $optimized = 0;

        foreach ($candidates as $media) {
            $optimizedPath = ImageUploadService::optimize($media->file_path);

            if ($optimizedPath === null) {
                $this->line("  #{$media->id} ({$media->file_path}): übersprungen (Datei fehlt oder Format nicht unterstützt).");

                continue;
            }

            $media->update(['optimized_path' => $optimizedPath]);
            $this->line("  #{$media->id}: {$media->file_path} -> {$optimizedPath}");
            $optimized++;
        }

        $this->info("Fertig: {$optimized}/{$candidates->count()} Medien optimiert.");

        return self::SUCCESS;
    }
}

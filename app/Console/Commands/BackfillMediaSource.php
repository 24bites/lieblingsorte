<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * One-time data backfill for the media.source column, run once after the
 * migration on any environment that already has media rows predating it.
 * Existing rows all default to 'generated'; this corrects the ones that are
 * actually real Wikimedia photos (matched via credits.json) or already
 * AI-generated (matched via the '-ki-' filename marker used by
 * ImageUploadService::storeBinary() for AI images).
 */
class BackfillMediaSource extends Command
{
    protected $signature = 'media:backfill-source {--dry-run : Only report what would change}';

    protected $description = 'Backfill media.source for rows created before the column existed';

    public function handle(): int
    {
        $creditsPath = storage_path('app/credits.json');
        $wikimediaPaths = [];

        if (file_exists($creditsPath)) {
            $credits = json_decode(file_get_contents($creditsPath), true) ?: [];
            $wikimediaPaths = array_flip(array_column($credits, 'file'));
        }

        $dryRun = (bool) $this->option('dry-run');
        $counts = ['wikimedia' => 0, 'ai' => 0, 'unchanged' => 0];

        Media::query()->orderBy('id')->chunkById(200, function ($chunk) use (&$counts, $wikimediaPaths, $dryRun) {
            foreach ($chunk as $media) {
                $source = match (true) {
                    isset($wikimediaPaths[$media->file_path]) => 'wikimedia',
                    Str::contains($media->file_path, '-ki-') => 'ai',
                    default => null,
                };

                if ($source === null || $media->source === $source) {
                    $counts['unchanged']++;

                    continue;
                }

                $counts[$source]++;
                $this->line("  {$media->file_path} -> {$source}");

                if (! $dryRun) {
                    $media->update(['source' => $source]);
                }
            }
        });

        $this->info(sprintf(
            '%sDone. wikimedia: %d, ai: %d, unchanged: %d.',
            $dryRun ? '[DRY RUN] ' : '',
            $counts['wikimedia'],
            $counts['ai'],
            $counts['unchanged']
        ));

        return self::SUCCESS;
    }
}

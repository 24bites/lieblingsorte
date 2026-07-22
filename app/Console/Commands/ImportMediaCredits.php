<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;

/**
 * One-time import of storage/app/credits.json (written by FetchRealPhotos
 * before Media had its own credit_* columns) into the Media table, so the
 * admin media library and the public Bildquellen page can maintain and read
 * credits directly from the database instead of the sidecar JSON file.
 */
class ImportMediaCredits extends Command
{
    protected $signature = 'media:import-credits {--dry-run : Only report what would change}';

    protected $description = 'Import storage/app/credits.json into the media table\'s credit_* columns';

    public function handle(): int
    {
        $path = storage_path('app/credits.json');

        if (! file_exists($path)) {
            $this->info('Keine credits.json gefunden - nichts zu importieren.');

            return self::SUCCESS;
        }

        $credits = json_decode(file_get_contents($path), true) ?: [];
        $dryRun = (bool) $this->option('dry-run');
        $counts = ['imported' => 0, 'skipped_no_media' => 0, 'skipped_has_credit' => 0];

        foreach ($credits as $credit) {
            $media = Media::where('file_path', $credit['file'] ?? null)->first();

            if (! $media) {
                $counts['skipped_no_media']++;

                continue;
            }

            if ($media->hasCredit()) {
                $counts['skipped_has_credit']++;

                continue;
            }

            $this->line("  {$media->file_path} -> {$credit['author']} ({$credit['license']})");
            $counts['imported']++;

            if (! $dryRun) {
                $media->update([
                    'credit_author' => Media::truncateCreditText($credit['author'] ?? null),
                    'credit_license' => $credit['license'] ?? null,
                    'credit_source_title' => $credit['source_title'] ?? null,
                    'credit_source_url' => $credit['source_url'] ?? null,
                ]);
            }
        }

        $this->info(sprintf(
            '%sFertig. importiert: %d, ohne passendes Medium: %d, bereits mit Quelle: %d.',
            $dryRun ? '[DRY RUN] ' : '',
            $counts['imported'],
            $counts['skipped_no_media'],
            $counts['skipped_has_credit']
        ));

        return self::SUCCESS;
    }
}

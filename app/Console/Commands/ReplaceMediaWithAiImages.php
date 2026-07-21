<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\Region;
use App\Support\AiCronSettings;
use App\Support\AiImagePromptBuilder;
use App\Support\ImageUploadService;
use App\Support\OpenAiImageGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Replaces Wikimedia photos and offline GD placeholder illustrations with
 * AI-generated photos, a few at a time. Manually uploaded images
 * (source=upload) are never touched. Runs on a schedule (see
 * routes/console.php); intended to eventually cover every Region/TravelTip
 * that has no manually uploaded image of its own.
 */
class ReplaceMediaWithAiImages extends Command
{
    protected $signature = 'images:ai-replace {--limit=5 : Maximum number of media rows to replace per run}';

    protected $description = 'Replace Wikimedia photos and generated placeholders with AI-generated images';

    public function handle(): int
    {
        if (! AiCronSettings::enabled()) {
            $this->info('KI-Crons sind in den Einstellungen deaktiviert - überspringe.');

            return self::SUCCESS;
        }

        if (! OpenAiImageGenerator::isConfigured()) {
            $this->warn('OPENAI_API_KEY ist nicht konfiguriert - überspringe.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));

        $candidates = Media::query()
            ->whereIn('source', ['wikimedia', 'generated'])
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->load('mediable');

        if ($candidates->isEmpty()) {
            $this->info('Keine Bilder zum Ersetzen gefunden.');

            return self::SUCCESS;
        }

        set_time_limit(60 * $candidates->count() + 60);

        foreach ($candidates as $media) {
            $this->replace($media);
        }

        return self::SUCCESS;
    }

    private function replace(Media $media): void
    {
        $model = $media->mediable;

        if ($model === null) {
            $this->line("  #{$media->id}: verwaistes Medienobjekt, überspringe.");

            return;
        }

        $prompt = AiImagePromptBuilder::forModel($model);
        $directory = dirname($media->file_path);
        $slug = $model->slug;
        $oldPath = $media->file_path;

        try {
            $contents = OpenAiImageGenerator::generate($prompt);
            $newPath = ImageUploadService::storeBinary($contents, $directory, $slug.'-ki');
        } catch (Throwable $e) {
            Log::warning('images:ai-replace: Ersetzen fehlgeschlagen.', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);
            $this->line("  #{$media->id} ({$oldPath}): fehlgeschlagen - {$e->getMessage()}");

            return;
        }

        $media->update(['file_path' => $newPath, 'source' => 'ai']);

        if ($model instanceof Region && $media->is_cover) {
            $model->update(['hero_image' => $newPath]);
        }

        Storage::disk('public')->delete($oldPath);
        Storage::disk('public')->delete(preg_replace('/\.[^.]+$/', '.webp', $oldPath));

        $this->line("  #{$media->id}: {$oldPath} -> {$newPath}");
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Region;
use App\Models\TravelTip;
use App\Support\AiCronSettings;
use App\Support\AiImagePromptBuilder;
use App\Support\ImageUploadService;
use App\Support\OpenAiConfig;
use App\Support\OpenAiImageGenerator;
use App\Support\OpenAiRegionDrafter;
use App\Support\RegionDraftPersister;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Finishes a region up so it can go live unattended: a KI-Vorschläge region
 * once approved, or any manually created region, both flow through
 * Region::needsContentCompletion(). Advances one small step at a time
 * (one region image, one drafted tip, or one tip image per step) so a run
 * stays fast even though a fully bare region needs ~25 steps (1 cover image +
 * 12 tips + 12 tip images) to finish; once a region has its cover image, at
 * least 12 tips, and every tip has an image, it - and its tips - are
 * published and it's marked content_completed_at so it's never touched again.
 */
class CompleteRegionContent extends Command
{
    protected $signature = 'regions:complete-content {--steps=5 : Maximum number of generation steps per run}';

    protected $description = 'Generate missing region/tip images and tips for approved KI or manually created regions, then publish';

    private const TARGET_TIP_COUNT = 12;

    public function handle(): int
    {
        if (! AiCronSettings::enabled(AiCronSettings::REGIONS_COMPLETE_CONTENT)) {
            $this->info('KI-Crons sind in den Einstellungen deaktiviert - überspringe.');
            Log::info('regions:complete-content: übersprungen (in Einstellungen deaktiviert).');

            return self::SUCCESS;
        }

        if (! OpenAiConfig::isConfigured()) {
            $this->warn('OPENAI_API_KEY ist nicht konfiguriert - überspringe.');
            Log::info('regions:complete-content: übersprungen (kein OPENAI_API_KEY konfiguriert).');

            return self::SUCCESS;
        }

        $steps = max(1, (int) $this->option('steps'));
        $done = 0;
        // A region whose step fails this run is skipped for the rest of the
        // run (but retried fresh next run) so one problem region can't block
        // every other eligible region from making progress.
        $skipIds = [];

        for ($i = 0; $i < $steps; $i++) {
            $region = Region::needsContentCompletion()
                ->when($skipIds, fn ($q) => $q->whereNotIn('id', $skipIds))
                ->orderBy('id')
                ->first();

            if (! $region) {
                break;
            }

            if ($this->advance($region)) {
                $done++;
            } else {
                $skipIds[] = $region->id;
            }
        }

        Log::info("regions:complete-content: Lauf abgeschlossen. {$done} Schritt(e) ausgeführt.");

        return self::SUCCESS;
    }

    private function advance(Region $region): bool
    {
        if ($region->media()->where('is_cover', true)->doesntExist()) {
            return $this->generateCoverImage($region);
        }

        if ($region->travelTips()->count() < self::TARGET_TIP_COUNT) {
            return $this->draftOneTip($region);
        }

        $tipWithoutImage = $region->travelTips()->whereDoesntHave('media')->orderBy('sort_order')->first();
        if ($tipWithoutImage) {
            return $this->generateTipImage($tipWithoutImage);
        }

        $region->travelTips()->update(['is_published' => true]);
        $region->update(['is_published' => true, 'content_completed_at' => now()]);
        $this->line("  #{$region->id} ({$region->name}): fertiggestellt und veröffentlicht.");

        return true;
    }

    private function generateCoverImage(Region $region): bool
    {
        try {
            $contents = OpenAiImageGenerator::generate(AiImagePromptBuilder::forModel($region));
            $path = ImageUploadService::storeBinary($contents, "regions/{$region->slug}", $region->slug.'-ki');
        } catch (Throwable $e) {
            Log::warning('regions:complete-content: Regionsbild fehlgeschlagen.', [
                'region_id' => $region->id, 'error' => $e->getMessage(),
            ]);
            $this->line("  #{$region->id} ({$region->name}): Regionsbild fehlgeschlagen - {$e->getMessage()}");

            return false;
        }

        ImageUploadService::attach($region, $path, $region->name, true, (int) $region->media()->max('sort_order') + 1, 'ai');
        $region->update(['hero_image' => $path]);
        $this->line("  #{$region->id} ({$region->name}): Regionsbild erstellt.");

        return true;
    }

    private function draftOneTip(Region $region): bool
    {
        $existingTitles = $region->travelTips()->pluck('title')->all();

        try {
            $tipDraft = OpenAiRegionDrafter::draftAdditionalTip(
                $region->name,
                $region->country,
                $region->short_description,
                $existingTitles,
            );
            RegionDraftPersister::createTip($region, $tipDraft, $region->travelTips()->count());
        } catch (Throwable $e) {
            Log::warning('regions:complete-content: Tipp-Entwurf fehlgeschlagen.', [
                'region_id' => $region->id, 'error' => $e->getMessage(),
            ]);
            $this->line("  #{$region->id} ({$region->name}): Tipp-Entwurf fehlgeschlagen - {$e->getMessage()}");

            return false;
        }

        $this->line("  #{$region->id} ({$region->name}): Tipp \"{$tipDraft['title']}\" erstellt (".($region->travelTips()->count()).'/'.self::TARGET_TIP_COUNT.').');

        return true;
    }

    private function generateTipImage(TravelTip $tip): bool
    {
        try {
            $contents = OpenAiImageGenerator::generate(AiImagePromptBuilder::forModel($tip));
            $path = ImageUploadService::storeBinary($contents, "tips/{$tip->slug}", $tip->slug.'-ki');
        } catch (Throwable $e) {
            Log::warning('regions:complete-content: Tipp-Bild fehlgeschlagen.', [
                'tip_id' => $tip->id, 'error' => $e->getMessage(),
            ]);
            $this->line("  Tipp #{$tip->id} ({$tip->title}): Bild fehlgeschlagen - {$e->getMessage()}");

            return false;
        }

        ImageUploadService::attach($tip, $path, $tip->title, true, 0, 'ai');
        $this->line("  Tipp #{$tip->id} ({$tip->title}): Bild erstellt.");

        return true;
    }
}

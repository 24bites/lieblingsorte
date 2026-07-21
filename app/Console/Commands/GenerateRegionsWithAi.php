<?php

namespace App\Console\Commands;

use App\Models\Region;
use App\Support\OpenAiRegionDrafter;
use App\Support\RegionDraftPersister;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Suggests and drafts new regions via OpenAI, a few at a time, capped at a
 * fixed number per calendar day. Every created region is unpublished and
 * flagged ai_generated so it shows up in the admin "KI-Vorschläge" review
 * queue instead of going live automatically.
 */
class GenerateRegionsWithAi extends Command
{
    protected $signature = 'regions:auto-generate
        {--limit=2 : Maximum number of regions to draft per run}
        {--daily-cap=10 : Maximum number of AI-generated regions per calendar day}';

    protected $description = 'Suggest and draft new regions via OpenAI, queued for admin review';

    public function handle(): int
    {
        if (! OpenAiRegionDrafter::isConfigured()) {
            $this->warn('OPENAI_API_KEY ist nicht konfiguriert - überspringe.');

            return self::SUCCESS;
        }

        $dailyCap = max(1, (int) $this->option('daily-cap'));
        $createdToday = Region::where('ai_generated', true)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $remaining = min(max(1, (int) $this->option('limit')), $dailyCap - $createdToday);

        if ($remaining <= 0) {
            $this->info("Tageslimit von {$dailyCap} KI-Regionen bereits erreicht ({$createdToday}).");

            return self::SUCCESS;
        }

        for ($i = 0; $i < $remaining; $i++) {
            if (! $this->generateOne()) {
                break;
            }
        }

        return self::SUCCESS;
    }

    private function generateOne(): bool
    {
        $avoidNames = Region::pluck('name')->all();

        try {
            $placeName = OpenAiRegionDrafter::suggestPlaceName($avoidNames);
        } catch (Throwable $e) {
            Log::warning('regions:auto-generate: Vorschlag fehlgeschlagen.', ['error' => $e->getMessage()]);
            $this->line("  Vorschlag fehlgeschlagen: {$e->getMessage()}");

            return false;
        }

        if ($placeName === null) {
            $this->line('  Kein Vorschlag erhalten.');

            return false;
        }

        if (Region::whereRaw('LOWER(name) = ?', [mb_strtolower($placeName)])->exists()) {
            $this->line("  \"{$placeName}\" existiert bereits, überspringe.");

            return true;
        }

        try {
            $draft = OpenAiRegionDrafter::draft($placeName);
            $region = RegionDraftPersister::persist($draft, aiGenerated: true);
        } catch (Throwable $e) {
            Log::warning('regions:auto-generate: Entwurf fehlgeschlagen.', [
                'place_name' => $placeName,
                'error' => $e->getMessage(),
            ]);
            $this->line("  \"{$placeName}\": Entwurf fehlgeschlagen - {$e->getMessage()}");

            return true;
        }

        $this->line("  Erstellt: {$region->name} (#{$region->id}, unveröffentlicht, zur Prüfung vorgemerkt).");

        return true;
    }
}

<?php

namespace App\Console\Commands;

use App\Support\AiCronSettings;
use App\Support\OpenAiSocialCopywriter;
use App\Support\PinterestFeedSelector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Generates a Pinterest caption (via OpenAI, same brief the Social Hub's
 * "+Pinterest" button uses) for feed-eligible Regions/TravelTips that don't
 * have one yet. Runs once a day (see routes/console.php) rather than live
 * per feed request - Pinterest may re-fetch the feed frequently, and a live
 * OpenAI call per item per request would be slow and needlessly expensive.
 * Only genuinely new feed items (no cached SocialPost yet) trigger a call.
 */
class GeneratePinterestCaptions extends Command
{
    protected $signature = 'social:pinterest-captions {--limit=15 : Maximum number of captions to generate per run}';

    protected $description = "Generate Pinterest captions for feed-eligible items that don't have one yet";

    public function handle(): int
    {
        if (! AiCronSettings::enabled(AiCronSettings::PINTEREST_CAPTIONS)) {
            $this->info('KI-Crons sind in den Einstellungen deaktiviert - überspringe.');
            Log::info('social:pinterest-captions: übersprungen (in Einstellungen deaktiviert).');

            return self::SUCCESS;
        }

        if (! OpenAiSocialCopywriter::isConfigured()) {
            $this->warn('OPENAI_API_KEY ist nicht konfiguriert - überspringe.');
            Log::info('social:pinterest-captions: übersprungen (kein OPENAI_API_KEY konfiguriert).');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));

        $candidates = PinterestFeedSelector::eligibleItems()
            ->reject(fn ($item) => $item->socialPosts()->where('platform', 'pinterest')->exists())
            ->take($limit);

        if ($candidates->isEmpty()) {
            $this->info('Alle Feed-Einträge haben bereits eine Pinterest-Beschreibung.');
            Log::info('social:pinterest-captions: keine Kandidaten gefunden.');

            return self::SUCCESS;
        }

        $generated = 0;

        foreach ($candidates as $item) {
            if ($this->generateFor($item)) {
                $generated++;
            }
        }

        Log::info("social:pinterest-captions: Lauf abgeschlossen. {$generated}/{$candidates->count()} Captions erzeugt.");

        return self::SUCCESS;
    }

    private function generateFor($item): bool
    {
        $shareData = $item->socialShareData();

        try {
            $caption = OpenAiSocialCopywriter::write('pinterest', $shareData);
        } catch (Throwable $e) {
            Log::warning('social:pinterest-captions: Erzeugen fehlgeschlagen.', [
                'item' => $item::class.'#'.$item->id,
                'error' => $e->getMessage(),
            ]);
            $this->line("  {$shareData['title']}: fehlgeschlagen - {$e->getMessage()}");

            return false;
        }

        $item->socialPosts()->updateOrCreate(
            ['platform' => 'pinterest'],
            ['caption' => $caption, 'link_url' => $shareData['url'], 'image_url' => $shareData['image']],
        );

        $this->line("  {$shareData['title']}: Caption erzeugt.");

        return true;
    }
}

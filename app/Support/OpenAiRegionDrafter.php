<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Uses OpenAI's chat completions endpoint to draft a full region plus travel
 * tips from a single place name. The result is always saved unpublished so a
 * human can review and correct it before it goes live.
 */
class OpenAiRegionDrafter
{
    public static function isConfigured(): bool
    {
        return OpenAiConfig::isConfigured();
    }

    public static function draft(string $placeName, int $tipCount = 15): array
    {
        $apiKey = OpenAiConfig::apiKey();

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY ist nicht konfiguriert.');
        }

        $schema = <<<'JSON'
        {
          "name": "string, z. B. Ort- oder Regionsname",
          "type": "einer von: Region, Stadt, Insel, Reisegebiet",
          "country": "string",
          "federal_state": "string oder null",
          "best_travel_time": "string oder null",
          "short_description": "max. 200 Zeichen",
          "description": "mehrere Absätze Fließtext",
          "arrival_information": "string oder null",
          "latitude": "Dezimalzahl oder null",
          "longitude": "Dezimalzahl oder null",
          "seo_title": "string oder null",
          "seo_description": "string oder null",
          "tips": [
            {
              "title": "string",
              "short_description": "max. 200 Zeichen",
              "description": "mehrere Sätze Fließtext",
              "highlights": ["string", "..."],
              "location_name": "string oder null",
              "address": "string oder null",
              "latitude": "Dezimalzahl oder null",
              "longitude": "Dezimalzahl oder null",
              "duration": "string oder null",
              "difficulty": "einer von: leicht, mittel, anspruchsvoll, oder null",
              "best_season": "string oder null",
              "price_information": "string oder null",
              "opening_hours": "string oder null",
              "parking_information": "string oder null",
              "arrival_information": "string oder null",
              "website_url": "string oder null",
              "phone": "string oder null",
              "email": "string oder null",
              "rating": "Zahl zwischen 0 und 5 oder null",
              "family_friendly": "boolean",
              "stroller_friendly": "boolean",
              "dog_friendly": "boolean",
              "indoor": "boolean",
              "free_entry": "boolean",
              "featured": "boolean"
            }
          ]
        }
        JSON;

        $model = config('services.openai.text_model', 'gpt-4o-mini');

        $response = Http::withToken($apiKey)
            ->timeout(180)
            ->retry(2, 1000)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Du bist ein erfahrener deutschsprachiger Reiseredakteur. Du recherchierst '
                            .'sorgfältig, erfindest keine falschen Fakten (Adressen, Preise, Öffnungszeiten dürfen '
                            .'"null" sein, wenn du sie nicht sicher weißt) und schreibst in einem einladenden, '
                            .'aber sachlichen Reiseführer-Stil auf Deutsch.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Erstelle einen Entwurf für die Reiseregion oder Stadt \"{$placeName}\" mit "
                            ."genau {$tipCount} unterschiedlichen, konkreten Reisetipps (Sehenswürdigkeiten, "
                            .'Aktivitäten, Restaurants, Aussichtspunkte etc., keine Duplikate). '
                            .'Antworte ausschließlich mit einem JSON-Objekt exakt nach folgendem Schema '
                            ."(Werte sind Beispiele für den Typ, keine Vorgabe für den Inhalt):\n{$schema}",
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenAI-Anfrage fehlgeschlagen: '.$response->json('error.message', (string) $response->status())
            );
        }

        $content = $response->json('choices.0.message.content');
        $data = json_decode((string) $content, true);

        if (! is_array($data) || empty($data['name']) || empty($data['tips']) || ! is_array($data['tips'])) {
            throw new RuntimeException('OpenAI-Antwort konnte nicht als gültiger Regions-Entwurf gelesen werden.');
        }

        AiUsageTracker::recordChatUsage('region_draft', $model, $response->json('usage', []));

        return $data;
    }

    /**
     * Asks for a single new, real travel destination not already in
     * $avoidNames (existing or previously rejected regions), for the
     * regions:auto-generate cron. Returns null if OpenAI can't produce a
     * usable suggestion.
     */
    public static function suggestPlaceName(array $avoidNames): ?string
    {
        $apiKey = OpenAiConfig::apiKey();

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY ist nicht konfiguriert.');
        }

        $avoidList = empty($avoidNames) ? 'keine' : implode(', ', $avoidNames);

        $model = config('services.openai.text_model', 'gpt-4o-mini');

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->retry(2, 1000)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.9,
                'max_tokens' => 200,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Du bist ein erfahrener deutschsprachiger Reiseredakteur für ein Reiseportal. '
                            .'Du schlägst ausschließlich echte, existierende Reiseziele vor (Regionen, Städte, '
                            .'Inseln), niemals erfundene Orte.',
                    ],
                    [
                        'role' => 'user',
                        'content' => 'Schlage genau EIN neues, attraktives Reiseziel für das Portal vor, das noch '
                            .'nicht in dieser Liste bereits vorhandener oder abgelehnter Ziele enthalten ist: '
                            ."{$avoidList}. Antworte ausschließlich mit einem JSON-Objekt der Form "
                            .'{"name": "Ortsname, Land"}.',
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenAI-Anfrage fehlgeschlagen: '.$response->json('error.message', (string) $response->status())
            );
        }

        $content = $response->json('choices.0.message.content');
        $data = json_decode((string) $content, true);
        $name = is_array($data) ? trim((string) ($data['name'] ?? '')) : '';

        AiUsageTracker::recordChatUsage('region_place_name', $model, $response->json('usage', []));

        return $name !== '' ? $name : null;
    }

    /**
     * Drafts one additional travel tip for an already-existing region, for
     * the regions:complete-content cron topping a region up to 12 tips.
     * Avoids duplicating $existingTitles.
     */
    public static function draftAdditionalTip(string $regionName, string $regionCountry, string $regionDescription, array $existingTitles): array
    {
        $apiKey = OpenAiConfig::apiKey();

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY ist nicht konfiguriert.');
        }

        $existingList = empty($existingTitles) ? 'keine' : implode(', ', $existingTitles);

        $schema = <<<'JSON'
        {
          "title": "string",
          "short_description": "max. 200 Zeichen",
          "description": "mehrere Sätze Fließtext",
          "highlights": ["string", "..."],
          "location_name": "string oder null",
          "address": "string oder null",
          "latitude": "Dezimalzahl oder null",
          "longitude": "Dezimalzahl oder null",
          "duration": "string oder null",
          "difficulty": "einer von: leicht, mittel, anspruchsvoll, oder null",
          "best_season": "string oder null",
          "price_information": "string oder null",
          "opening_hours": "string oder null",
          "parking_information": "string oder null",
          "arrival_information": "string oder null",
          "website_url": "string oder null",
          "phone": "string oder null",
          "email": "string oder null",
          "rating": "Zahl zwischen 0 und 5 oder null",
          "family_friendly": "boolean",
          "stroller_friendly": "boolean",
          "dog_friendly": "boolean",
          "indoor": "boolean",
          "free_entry": "boolean",
          "featured": "boolean"
        }
        JSON;

        $model = config('services.openai.text_model', 'gpt-4o-mini');

        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->retry(2, 1000)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.8,
                'max_tokens' => 1500,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Du bist ein erfahrener deutschsprachiger Reiseredakteur. Du recherchierst '
                            .'sorgfältig, erfindest keine falschen Fakten (Adressen, Preise, Öffnungszeiten dürfen '
                            .'"null" sein, wenn du sie nicht sicher weißt) und schreibst in einem einladenden, '
                            .'aber sachlichen Reiseführer-Stil auf Deutsch.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Ergänze für die Reiseregion oder Stadt \"{$regionName}\" ({$regionCountry}) "
                            .'einen weiteren, konkreten Reisetipp (Sehenswürdigkeit, Aktivität, Restaurant, '
                            .'Aussichtspunkt o. Ä.). Kontext zur Region: '.$regionDescription."\n"
                            .'Er darf keinen der bereits vorhandenen Tipps duplizieren oder sehr ähnlich sein: '
                            ."{$existingList}. Antworte ausschließlich mit einem JSON-Objekt exakt nach folgendem "
                            ."Schema (Werte sind Beispiele für den Typ, keine Vorgabe für den Inhalt):\n{$schema}",
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenAI-Anfrage fehlgeschlagen: '.$response->json('error.message', (string) $response->status())
            );
        }

        $content = $response->json('choices.0.message.content');
        $data = json_decode((string) $content, true);

        if (! is_array($data) || empty($data['title'])) {
            throw new RuntimeException('OpenAI-Antwort konnte nicht als gültiger Tipp-Entwurf gelesen werden.');
        }

        AiUsageTracker::recordChatUsage('region_tip_draft', $model, $response->json('usage', []));

        return $data;
    }
}

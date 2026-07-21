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

        $response = Http::withToken($apiKey)
            ->timeout(180)
            ->retry(2, 1000)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.text_model', 'gpt-4o-mini'),
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.7,
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

        return $data;
    }
}

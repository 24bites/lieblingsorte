<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

/**
 * Writes a full Pinterest pin copy brief (overlay text + SEO title +
 * description) for one point of interest from one specific "angle" (e.g.
 * family trip vs. insider tip). Kept separate from OpenAiSocialCopywriter
 * because Pinterest needs several structured fields at once, not one
 * freeform caption, and each angle is only ever generated once per POI
 * (the caller caches the result as a PinterestPin row instead of
 * regenerating on every view - "fewer but better pins").
 */
class PinterestPinWriter
{
    public const ANGLES = [
        'allgemein' => 'Allgemeiner, einladender Blickwinkel für ein breites Publikum.',
        'familie' => 'Blickwinkel für Familien mit Kindern: Sicherheit, Kinderfreundlichkeit, praktische Hinweise fuer den Ausflug.',
        'geheimtipp' => 'Blickwinkel als Geheimtipp/Insider-Tipp: wenig bekannt, ruhig, "kaum Touristen", besonders fuer Entdecker.',
    ];

    public static function isConfigured(): bool
    {
        return OpenAiConfig::isConfigured();
    }

    /**
     * @return array{overlay_headline: string, overlay_subline: ?string, pin_title: string, pin_description: string}
     */
    public static function write(string $angle, array $shareData): array
    {
        if (! array_key_exists($angle, self::ANGLES)) {
            throw new InvalidArgumentException("Unbekannter Blickwinkel: {$angle}");
        }

        $apiKey = OpenAiConfig::apiKey();

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY ist nicht konfiguriert.');
        }

        $angleBrief = self::ANGLES[$angle];
        $title = $shareData['title'] ?? '';
        $description = $shareData['description'] ?? '';

        $model = config('services.openai.text_model', 'gpt-4o-mini');

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->retry(2, 1000)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'temperature' => 0.8,
                'max_tokens' => 400,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Du bist Pinterest-SEO-Texter/in fuer ein deutschsprachiges Reiseportal (24bites.de). '
                            .'Du bleibst bei den gegebenen Fakten und erfindest keine neuen Details, Preise oder '
                            .'Behauptungen. Kein Clickbait. Antworte ausschliesslich mit einem JSON-Objekt mit genau '
                            .'diesen Schluesseln: "overlay_headline" (kurzer, praegnanter Text fuers Bild, max. 45 '
                            .'Zeichen), "overlay_subline" (kurzer Zusatztext fuers Bild, max. 40 Zeichen, oder null '
                            .'wenn nicht sinnvoll), "pin_title" (Pinterest-Pin-Titel, keywordreich, wichtigstes '
                            .'Keyword zuerst, max. 100 Zeichen), "pin_description" (Pinterest-Pin-Beschreibung, '
                            .'such-freundlich und beschreibend wie bei einer visuellen Suchmaschine, 300-500 '
                            .'Zeichen, endet mit 2-3 passenden Hashtags).',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Blickwinkel: {$angleBrief}\n\nOrt/Thema: \"{$title}\"\nBeschreibung: {$description}",
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenAI-Anfrage fehlgeschlagen: '.$response->json('error.message', (string) $response->status())
            );
        }

        $model = $response->json('model', $model);
        $raw = trim((string) $response->json('choices.0.message.content'));
        $decoded = json_decode($raw, true);

        if (! is_array($decoded) || blank($decoded['overlay_headline'] ?? null) || blank($decoded['pin_title'] ?? null) || blank($decoded['pin_description'] ?? null)) {
            throw new RuntimeException('OpenAI-Antwort enthielt kein gueltiges Pin-Brief-JSON.');
        }

        AiUsageTracker::recordChatUsage('pinterest_pin_copy', $model, $response->json('usage', []));

        return [
            'overlay_headline' => trim($decoded['overlay_headline']),
            'overlay_subline' => filled($decoded['overlay_subline'] ?? null) ? trim($decoded['overlay_subline']) : null,
            'pin_title' => trim($decoded['pin_title']),
            'pin_description' => trim($decoded['pin_description']),
        ];
    }
}

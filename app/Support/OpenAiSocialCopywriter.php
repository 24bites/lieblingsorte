<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

/**
 * Writes a platform-appropriate social media caption for a Region, TravelTip,
 * or TravelReport via OpenAI's chat completions endpoint. Each platform gets
 * its own tone/length rules rather than one generic "write a social post"
 * instruction, since a good Pinterest description and a good WhatsApp
 * forward message look nothing alike.
 */
class OpenAiSocialCopywriter
{
    private const PLATFORM_BRIEFS = [
        'pinterest' => 'Schreibe eine Pinterest-Pin-Beschreibung: beschreibend und such-freundlich (Pinterest '
            .'funktioniert wie eine visuelle Suchmaschine), 100-200 Zeichen, 1-2 passende Emojis, 2-3 thematisch '
            .'passende Hashtags am Ende. Kein "Link in Bio" o. Ä., der Link wird separat hinterlegt.',
        'facebook' => 'Schreibe einen Facebook-Post: einladender, persönlicher Ton, 2-4 Sätze, endet mit einer '
            .'kurzen Handlungsaufforderung (z. B. zum Weiterlesen). Höchstens 1-2 Hashtags am Ende, sparsam '
            .'mit Emojis (maximal 1-2).',
        'x' => 'Schreibe einen Beitrag für X (ehemals Twitter): prägnant, maximal 260 Zeichen insgesamt (der Link '
            .'wird separat angehängt und zählt nicht mit), 1-2 Hashtags.',
        'telegram' => 'Schreibe einen Telegram-Kanal-Post: ähnlich wie ein Facebook-Post, etwas direkter und '
            .'informeller, 2-4 Sätze, sparsam mit Emojis (maximal 1-2).',
        'whatsapp' => 'Schreibe eine kurze WhatsApp-Weiterleitungsnachricht, so wie man sie einer Freundin/einem '
            .'Freund schicken würde: 1-2 Sätze, sehr persönlich und locker, kein Hashtag.',
    ];

    public static function isConfigured(): bool
    {
        return OpenAiConfig::isConfigured();
    }

    public static function write(string $platform, array $shareData): string
    {
        if (! array_key_exists($platform, self::PLATFORM_BRIEFS)) {
            throw new InvalidArgumentException("Unbekannte Plattform: {$platform}");
        }

        $apiKey = OpenAiConfig::apiKey();

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY ist nicht konfiguriert.');
        }

        $brief = self::PLATFORM_BRIEFS[$platform];
        $title = $shareData['title'] ?? '';
        $description = $shareData['description'] ?? '';

        $model = config('services.openai.text_model', 'gpt-4o-mini');

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->retry(2, 1000)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'temperature' => 0.9,
                'max_tokens' => 300,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Du schreibst Social-Media-Texte auf Deutsch für ein Reiseportal. Du bleibst '
                            .'bei den gegebenen Fakten und erfindest keine neuen Details, Preise oder Behauptungen. '
                            .'Kein Clickbait, keine übertriebenen Superlative. Antworte ausschließlich mit dem '
                            .'fertigen Text, ohne Anführungszeichen, ohne Erklärung.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "{$brief}\n\nThema: \"{$title}\"\nBeschreibung: {$description}",
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenAI-Anfrage fehlgeschlagen: '.$response->json('error.message', (string) $response->status())
            );
        }

        $caption = trim((string) $response->json('choices.0.message.content'));

        if ($caption === '') {
            throw new RuntimeException('OpenAI-Antwort enthielt keinen Text.');
        }

        AiUsageTracker::recordChatUsage('social_caption', $model, $response->json('usage', []));

        return $caption;
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Wraps OpenAI's image generation endpoint. Used by the admin UI as an
 * alternative to manual uploads or the offline GD-based ImageGenerator.
 */
class OpenAiImageGenerator
{
    public static function isConfigured(): bool
    {
        return OpenAiConfig::isConfigured();
    }

    public static function generate(string $prompt, string $size = '1024x1024'): string
    {
        $apiKey = OpenAiConfig::apiKey();

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY ist nicht konfiguriert.');
        }

        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->retry(2, 500)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => config('services.openai.image_model', 'gpt-image-1'),
                'prompt' => $prompt,
                'size' => $size,
                'n' => 1,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenAI-Bildgenerierung fehlgeschlagen: '.$response->json('error.message', (string) $response->status())
            );
        }

        $b64 = $response->json('data.0.b64_json');

        if (blank($b64)) {
            throw new RuntimeException('OpenAI-Antwort enthielt kein Bild.');
        }

        $binary = base64_decode($b64, true);

        if ($binary === false) {
            throw new RuntimeException('OpenAI-Bilddaten konnten nicht dekodiert werden.');
        }

        return $binary;
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Actually sends a post via the Telegram Bot API (bot token + chat/channel
 * id, no OAuth app review needed) - the one platform of the five where real,
 * click-free automation is realistic without a business API approval
 * process. See TelegramConfig for where the credentials are stored.
 */
class TelegramPublisher
{
    public static function isConfigured(): bool
    {
        return TelegramConfig::isConfigured();
    }

    public static function send(string $caption, string $url, ?string $imageUrl = null): void
    {
        $token = TelegramConfig::botToken();
        $chatId = TelegramConfig::chatId();

        if (blank($token) || blank($chatId)) {
            throw new RuntimeException('Telegram ist nicht konfiguriert.');
        }

        $text = trim("{$caption}\n\n{$url}");

        if ($imageUrl) {
            $endpoint = "https://api.telegram.org/bot{$token}/sendPhoto";
            $payload = ['chat_id' => $chatId, 'photo' => $imageUrl, 'caption' => mb_substr($text, 0, 1024)];
        } else {
            $endpoint = "https://api.telegram.org/bot{$token}/sendMessage";
            $payload = ['chat_id' => $chatId, 'text' => mb_substr($text, 0, 4096)];
        }

        $response = Http::timeout(30)->post($endpoint, $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                'Telegram-Anfrage fehlgeschlagen: '.$response->json('description', (string) $response->status())
            );
        }
    }
}

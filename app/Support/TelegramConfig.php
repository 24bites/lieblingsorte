<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Resolves the Telegram bot token/chat id used for actually sending Social
 * Hub posts (not just building a share link). The bot token is stored
 * encrypted at rest, same pattern as OpenAiConfig. Chat id is stored plain -
 * it's a channel/group identifier, not a secret.
 */
class TelegramConfig
{
    public static function botToken(): ?string
    {
        $encrypted = Setting::get('telegram_bot_token', '');

        if (blank($encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null;
        }
    }

    public static function chatId(): ?string
    {
        $chatId = Setting::get('telegram_chat_id', '');

        return filled($chatId) ? $chatId : null;
    }

    public static function isConfigured(): bool
    {
        return filled(self::botToken()) && filled(self::chatId());
    }

    public static function store(string $botToken, string $chatId): void
    {
        Setting::set('telegram_bot_token', Crypt::encryptString($botToken));
        Setting::set('telegram_chat_id', $chatId);
    }

    public static function clear(): void
    {
        Setting::set('telegram_bot_token', '');
        Setting::set('telegram_chat_id', '');
    }

    public static function preview(): ?string
    {
        $token = self::botToken();

        return $token !== null ? '••••'.substr($token, -4) : null;
    }
}

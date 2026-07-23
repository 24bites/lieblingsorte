<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Resolves the Anthropic (Claude) API key: an admin-configured key
 * (Settings, encrypted at rest via APP_KEY) takes precedence over the
 * ANTHROPIC_API_KEY env var, same pattern as OpenAiConfig.
 */
class AnthropicConfig
{
    public static function apiKey(): ?string
    {
        $encrypted = Setting::get('anthropic_api_key', '');

        if (filled($encrypted)) {
            try {
                return Crypt::decryptString($encrypted);
            } catch (DecryptException) {
                // Falls through to the env fallback, e.g. after an APP_KEY rotation
                // makes a previously-stored value undecryptable.
            }
        }

        return config('services.anthropic.key');
    }

    public static function isConfigured(): bool
    {
        return filled(self::apiKey());
    }

    public static function store(string $plainTextKey): void
    {
        Setting::set('anthropic_api_key', Crypt::encryptString($plainTextKey));
    }

    public static function clear(): void
    {
        Setting::set('anthropic_api_key', '');
    }

    public static function preview(): ?string
    {
        $key = self::storedKey();

        return $key !== null ? '••••'.substr($key, -4) : null;
    }

    private static function storedKey(): ?string
    {
        $encrypted = Setting::get('anthropic_api_key', '');

        if (blank($encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (DecryptException) {
            return null;
        }
    }
}

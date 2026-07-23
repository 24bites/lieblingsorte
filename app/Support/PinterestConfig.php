<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Owns the Pinterest OAuth app credentials and connection tokens (all
 * encrypted at rest via Setting, same pattern as OpenAiConfig/ResendConfig).
 * isConfigured() gates real publishing everywhere else in the app - it only
 * becomes true once an admin has completed the OAuth connect flow.
 */
class PinterestConfig
{
    public static function appId(): ?string
    {
        $appId = Setting::get('pinterest_app_id', '');

        return filled($appId) ? $appId : null;
    }

    public static function appSecret(): ?string
    {
        return self::decrypt(Setting::get('pinterest_app_secret', ''));
    }

    public static function hasAppCredentials(): bool
    {
        return filled(self::appId()) && filled(self::appSecret());
    }

    public static function storeAppCredentials(string $appId, string $appSecret): void
    {
        Setting::set('pinterest_app_id', $appId);
        Setting::set('pinterest_app_secret', Crypt::encryptString($appSecret));
    }

    public static function clearAppCredentials(): void
    {
        Setting::set('pinterest_app_id', '');
        Setting::set('pinterest_app_secret', '');
        self::clearTokens();
    }

    public static function appSecretPreview(): ?string
    {
        $secret = self::appSecret();

        return $secret !== null ? '••••'.substr($secret, -4) : null;
    }

    public static function accountUsername(): ?string
    {
        $username = Setting::get('pinterest_account_username', '');

        return filled($username) ? $username : null;
    }

    public static function isConfigured(): bool
    {
        return filled(self::rawAccessToken());
    }

    public static function storeTokens(string $accessToken, ?string $refreshToken, int $expiresInSeconds): void
    {
        Setting::set('pinterest_access_token', Crypt::encryptString($accessToken));

        if (filled($refreshToken)) {
            Setting::set('pinterest_refresh_token', Crypt::encryptString($refreshToken));
        }

        Setting::set('pinterest_token_expires_at', now()->addSeconds($expiresInSeconds)->toIso8601String());
    }

    public static function storeAccountUsername(?string $username): void
    {
        Setting::set('pinterest_account_username', $username ?? '');
    }

    public static function clearTokens(): void
    {
        Setting::set('pinterest_access_token', '');
        Setting::set('pinterest_refresh_token', '');
        Setting::set('pinterest_token_expires_at', '');
        Setting::set('pinterest_account_username', '');
    }

    public static function tokenExpiresAt(): ?Carbon
    {
        $value = Setting::get('pinterest_token_expires_at', '');

        return filled($value) ? Carbon::parse($value) : null;
    }

    /**
     * Returns a usable access token, transparently refreshing it first if it
     * has expired or is expiring soon. Returns null if not connected, or if
     * the refresh attempt itself fails (e.g. the refresh token was revoked) -
     * callers should treat that as "not connected" and surface it as such.
     */
    public static function validAccessToken(): ?string
    {
        if (! self::isConfigured() || ! self::hasAppCredentials()) {
            return null;
        }

        $expiresAt = self::tokenExpiresAt();

        if ($expiresAt !== null && $expiresAt->isAfter(now()->addMinutes(10))) {
            return self::rawAccessToken();
        }

        $refreshToken = self::rawRefreshToken();

        if (blank($refreshToken)) {
            return self::rawAccessToken();
        }

        try {
            $tokens = PinterestApiClient::refreshAccessToken(self::appId(), self::appSecret(), $refreshToken);
        } catch (Throwable) {
            return null;
        }

        self::storeTokens($tokens['access_token'], $tokens['refresh_token'], $tokens['expires_in']);

        return $tokens['access_token'];
    }

    private static function rawAccessToken(): ?string
    {
        return self::decrypt(Setting::get('pinterest_access_token', ''));
    }

    private static function rawRefreshToken(): ?string
    {
        return self::decrypt(Setting::get('pinterest_refresh_token', ''));
    }

    private static function decrypt(string $encrypted): ?string
    {
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

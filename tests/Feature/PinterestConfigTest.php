<?php

namespace Tests\Feature;

use App\Support\PinterestConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PinterestConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_not_configured_by_default(): void
    {
        $this->assertFalse(PinterestConfig::isConfigured());
        $this->assertFalse(PinterestConfig::hasAppCredentials());
        $this->assertNull(PinterestConfig::validAccessToken());
    }

    public function test_valid_access_token_returns_stored_token_when_not_expiring_soon(): void
    {
        PinterestConfig::storeAppCredentials('app-id', 'app-secret');
        PinterestConfig::storeTokens('access-token', 'refresh-token', 2592000);

        $this->assertSame('access-token', PinterestConfig::validAccessToken());
    }

    public function test_valid_access_token_refreshes_when_expiring_soon(): void
    {
        PinterestConfig::storeAppCredentials('app-id', 'app-secret');
        PinterestConfig::storeTokens('old-access-token', 'refresh-token', 60);

        Http::fake(['api.pinterest.com/v5/oauth/token' => Http::response([
            'access_token' => 'new-access-token',
            'refresh_token' => 'new-refresh-token',
            'expires_in' => 2592000,
        ])]);

        $token = PinterestConfig::validAccessToken();

        $this->assertSame('new-access-token', $token);
        $this->assertTrue(PinterestConfig::tokenExpiresAt()->isAfter(now()->addDays(29)));
    }

    public function test_valid_access_token_returns_null_when_refresh_fails(): void
    {
        PinterestConfig::storeAppCredentials('app-id', 'app-secret');
        PinterestConfig::storeTokens('old-access-token', 'refresh-token', 60);

        Http::fake(['api.pinterest.com/v5/oauth/token' => Http::response(['message' => 'invalid_grant'], 400)]);

        $this->assertNull(PinterestConfig::validAccessToken());
    }

    public function test_clear_app_credentials_also_clears_tokens(): void
    {
        PinterestConfig::storeAppCredentials('app-id', 'app-secret');
        PinterestConfig::storeTokens('access-token', 'refresh-token', 2592000);

        PinterestConfig::clearAppCredentials();

        $this->assertFalse(PinterestConfig::hasAppCredentials());
        $this->assertFalse(PinterestConfig::isConfigured());
    }

    public function test_app_secret_preview_masks_the_secret(): void
    {
        PinterestConfig::storeAppCredentials('app-id', 'super-secret-value');

        $this->assertSame('••••alue', PinterestConfig::appSecretPreview());
    }
}

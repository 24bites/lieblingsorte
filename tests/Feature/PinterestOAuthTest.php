<?php

namespace Tests\Feature;

use App\Support\PinterestConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PinterestOAuthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): \App\Models\User
    {
        return \App\Models\User::create([
            'name' => 'Administrator', 'email' => 'admin@lieblingsorte.test',
            'password' => \Illuminate\Support\Facades\Hash::make('admin123'), 'role' => 'admin',
        ]);
    }

    public function test_connect_redirects_to_settings_when_app_credentials_missing(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.pinterest.connect'));

        $response->assertRedirect(route('admin.settings.edit'));
        $response->assertSessionHasErrors('pinterest');
    }

    public function test_connect_redirects_to_pinterest_authorize_url_when_configured(): void
    {
        PinterestConfig::storeAppCredentials('app-id-123', 'app-secret-456');

        $response = $this->actingAs($this->admin())->get(route('admin.pinterest.connect'));

        $response->assertRedirect();
        $this->assertStringStartsWith('https://www.pinterest.com/oauth/', $response->headers->get('Location'));
        $this->assertStringContainsString('client_id=app-id-123', $response->headers->get('Location'));
    }

    public function test_callback_exchanges_code_and_stores_tokens(): void
    {
        PinterestConfig::storeAppCredentials('app-id-123', 'app-secret-456');

        Http::fake([
            'api.pinterest.com/v5/oauth/token' => Http::response([
                'access_token' => 'access-token-abc',
                'refresh_token' => 'refresh-token-xyz',
                'expires_in' => 2592000,
            ]),
            'api.pinterest.com/v5/user_account' => Http::response(['username' => '24bitesreisen']),
        ]);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.pinterest.connect'));

        $response = $this->actingAs($admin)->get(route('admin.pinterest.callback', [
            'code' => 'auth-code-1', 'state' => session('pinterest_oauth_state'),
        ]));

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertTrue(PinterestConfig::isConfigured());
        $this->assertSame('24bitesreisen', PinterestConfig::accountUsername());
    }

    public function test_callback_rejects_mismatched_state(): void
    {
        PinterestConfig::storeAppCredentials('app-id-123', 'app-secret-456');
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.pinterest.connect'));

        $response = $this->actingAs($admin)->get(route('admin.pinterest.callback', [
            'code' => 'auth-code-1', 'state' => 'wrong-state',
        ]));

        $response->assertSessionHasErrors('pinterest');
        $this->assertFalse(PinterestConfig::isConfigured());
    }

    public function test_callback_surfaces_pinterest_denial(): void
    {
        PinterestConfig::storeAppCredentials('app-id-123', 'app-secret-456');
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('admin.pinterest.callback', [
            'error' => 'access_denied', 'error_description' => 'Nutzer hat abgelehnt',
        ]));

        $response->assertSessionHasErrors('pinterest');
        $this->assertFalse(PinterestConfig::isConfigured());
    }

    public function test_disconnect_clears_tokens(): void
    {
        PinterestConfig::storeAppCredentials('app-id-123', 'app-secret-456');
        PinterestConfig::storeTokens('access-token', 'refresh-token', 2592000);
        $this->assertTrue(PinterestConfig::isConfigured());

        $response = $this->actingAs($this->admin())->post(route('admin.pinterest.disconnect'));

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertFalse(PinterestConfig::isConfigured());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.pinterest.connect'))->assertRedirect(route('admin.login'));
    }
}

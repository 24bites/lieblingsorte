<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PinterestConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSettingsPinterestAppTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Administrator', 'email' => 'admin@lieblingsorte.test',
            'password' => Hash::make('admin123'), 'role' => 'admin',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'Lieblingsorte',
            'site_claim' => 'Claim',
            'site_description' => 'Beschreibung',
            'contact_email' => 'hallo@lieblingsorte.test',
            'images_ai_replace_interval' => 10,
            'regions_auto_generate_interval' => 10,
            'regions_complete_content_interval' => 10,
        ], $overrides);
    }

    public function test_admin_can_set_pinterest_app_credentials_via_settings(): void
    {
        $response = $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validPayload([
            'pinterest_app_id' => '1593536',
            'pinterest_app_secret' => 'super-secret-value',
        ]));

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertTrue(PinterestConfig::hasAppCredentials());
        $this->assertSame('1593536', PinterestConfig::appId());
        $this->assertSame('super-secret-value', PinterestConfig::appSecret());
    }

    public function test_blank_submission_does_not_overwrite_existing_pinterest_app_secret(): void
    {
        PinterestConfig::storeAppCredentials('1593536', 'existing-secret');

        $response = $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validPayload([
            'pinterest_app_id' => '', 'pinterest_app_secret' => '',
        ]));

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertSame('existing-secret', PinterestConfig::appSecret());
    }

    public function test_remove_checkbox_clears_stored_pinterest_app_credentials_and_tokens(): void
    {
        PinterestConfig::storeAppCredentials('1593536', 'existing-secret');
        PinterestConfig::storeTokens('access-token', 'refresh-token', 2592000);

        $response = $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validPayload([
            'pinterest_app_id' => '', 'pinterest_app_secret' => '', 'remove_pinterest_app' => '1',
        ]));

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertFalse(PinterestConfig::hasAppCredentials());
        $this->assertFalse(PinterestConfig::isConfigured());
    }

    public function test_settings_edit_page_never_displays_the_raw_pinterest_app_secret(): void
    {
        PinterestConfig::storeAppCredentials('1593536', 'super-secret-value');

        $response = $this->actingAs($this->admin())->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertDontSee('super-secret-value');
        $response->assertSee('••••alue');
    }

    public function test_settings_page_shows_connect_button_once_app_credentials_are_set(): void
    {
        PinterestConfig::storeAppCredentials('1593536', 'existing-secret');

        $response = $this->actingAs($this->admin())->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertSee(route('admin.pinterest.connect'), false);
    }
}

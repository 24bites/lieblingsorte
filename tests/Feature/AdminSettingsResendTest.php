<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\ResendConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSettingsResendTest extends TestCase
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

    public function test_admin_can_set_resend_api_key_via_settings(): void
    {
        $response = $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validPayload([
            'resend_api_key' => 're_test_1234567890abcd',
        ]));

        $response->assertRedirect(route('admin.settings.edit'));
        // Stored encrypted at rest, never as plaintext.
        $this->assertNotSame('re_test_1234567890abcd', Setting::get('resend_api_key'));
        $this->assertTrue(ResendConfig::isConfigured());
        $this->assertSame('re_test_1234567890abcd', ResendConfig::apiKey());
    }

    public function test_blank_submission_does_not_overwrite_existing_resend_api_key(): void
    {
        ResendConfig::store('re_existing_key');

        $response = $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validPayload([
            'resend_api_key' => '',
        ]));

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertSame('re_existing_key', ResendConfig::apiKey());
    }

    public function test_remove_checkbox_clears_the_stored_resend_key(): void
    {
        config(['services.resend.key' => null]);
        ResendConfig::store('re_existing_key');

        $response = $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validPayload([
            'resend_api_key' => '',
            'remove_resend_api_key' => '1',
        ]));

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertSame('', Setting::get('resend_api_key'));
        $this->assertFalse(ResendConfig::isConfigured());
    }

    public function test_settings_edit_page_never_displays_the_raw_resend_key(): void
    {
        ResendConfig::store('re_super_secret_value');

        $response = $this->actingAs($this->admin())->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertDontSee('re_super_secret_value');
        $response->assertSee('••••alue');
    }

    public function test_settings_stored_resend_key_takes_precedence_over_env_config(): void
    {
        config(['services.resend.key' => 're_from_env']);
        ResendConfig::store('re_from_settings');

        $this->assertSame('re_from_settings', ResendConfig::apiKey());
    }

    public function test_env_config_is_used_when_no_resend_setting_is_stored(): void
    {
        config(['services.resend.key' => 're_from_env']);

        $this->assertSame('re_from_env', ResendConfig::apiKey());
    }

    public function test_app_service_provider_boot_feeds_configured_key_into_mail_config(): void
    {
        // Laravel's test harness boots the app once in setUp() and reuses it
        // across $this->get() calls, so simulating a request here wouldn't
        // re-trigger boot() - call it directly to test what it actually does.
        ResendConfig::store('re_boot_time_key');
        config(['services.resend.key' => null]);

        (new \App\Providers\AppServiceProvider(app()))->boot();

        $this->assertSame('re_boot_time_key', config('services.resend.key'));
    }
}

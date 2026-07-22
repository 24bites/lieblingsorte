<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\OpenAiConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSettingsOpenAiTest extends TestCase
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
        ], $overrides);
    }

    public function test_admin_can_set_openai_api_key_via_settings(): void
    {
        $response = $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validPayload([
            'openai_api_key' => 'sk-test-1234567890abcd',
        ]));

        $response->assertRedirect(route('admin.settings.edit'));
        // Stored encrypted at rest, never as plaintext.
        $this->assertNotSame('sk-test-1234567890abcd', Setting::get('openai_api_key'));
        $this->assertTrue(OpenAiConfig::isConfigured());
        $this->assertSame('sk-test-1234567890abcd', OpenAiConfig::apiKey());
    }

    public function test_blank_submission_does_not_overwrite_existing_openai_api_key(): void
    {
        OpenAiConfig::store('sk-existing-key');

        $response = $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validPayload([
            'openai_api_key' => '',
        ]));

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertSame('sk-existing-key', OpenAiConfig::apiKey());
    }

    public function test_remove_checkbox_clears_the_stored_key(): void
    {
        // Isolate from a real OPENAI_API_KEY possibly set in .env for local use —
        // this test only cares about the Settings-stored key being cleared.
        config(['services.openai.key' => null]);
        OpenAiConfig::store('sk-existing-key');

        $response = $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validPayload([
            'openai_api_key' => '',
            'remove_openai_api_key' => '1',
        ]));

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertSame('', Setting::get('openai_api_key'));
        $this->assertFalse(OpenAiConfig::isConfigured());
    }

    public function test_settings_edit_page_never_displays_the_raw_key(): void
    {
        OpenAiConfig::store('sk-super-secret-value');

        $response = $this->actingAs($this->admin())->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertDontSee('sk-super-secret-value');
        $response->assertSee('••••alue');
    }

    public function test_settings_stored_key_takes_precedence_over_env_config(): void
    {
        config(['services.openai.key' => 'sk-from-env']);
        OpenAiConfig::store('sk-from-settings');

        $this->assertSame('sk-from-settings', OpenAiConfig::apiKey());
    }

    public function test_env_config_is_used_when_no_setting_is_stored(): void
    {
        config(['services.openai.key' => 'sk-from-env']);

        $this->assertSame('sk-from-env', OpenAiConfig::apiKey());
    }
}

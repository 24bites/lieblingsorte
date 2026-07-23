<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AnthropicConfig;
use App\Support\TravelReportWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSettingsReportAiProviderTest extends TestCase
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
            'report_ai_provider' => 'openai',
        ], $overrides);
    }

    public function test_admin_can_switch_report_ai_provider_to_claude(): void
    {
        $response = $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validPayload([
            'report_ai_provider' => 'claude',
            'anthropic_api_key' => 'sk-ant-test-1234567890',
        ]));

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertSame('claude', TravelReportWriter::provider());
        $this->assertSame('sk-ant-test-1234567890', AnthropicConfig::apiKey());
    }

    public function test_invalid_provider_value_is_rejected(): void
    {
        $response = $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validPayload([
            'report_ai_provider' => 'gemini',
        ]));

        $response->assertSessionHasErrors('report_ai_provider');
    }

    public function test_blank_submission_does_not_overwrite_existing_anthropic_key(): void
    {
        AnthropicConfig::store('sk-ant-existing');

        $response = $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validPayload([
            'anthropic_api_key' => '',
        ]));

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertSame('sk-ant-existing', AnthropicConfig::apiKey());
    }

    public function test_remove_checkbox_clears_the_stored_anthropic_key(): void
    {
        config(['services.anthropic.key' => null]);
        AnthropicConfig::store('sk-ant-existing');

        $response = $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validPayload([
            'anthropic_api_key' => '', 'remove_anthropic_api_key' => '1',
        ]));

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertFalse(AnthropicConfig::isConfigured());
    }

    public function test_settings_edit_page_never_displays_the_raw_anthropic_key(): void
    {
        AnthropicConfig::store('sk-ant-super-secret-value');

        $response = $this->actingAs($this->admin())->get(route('admin.settings.edit'));

        $response->assertOk();
        $response->assertDontSee('sk-ant-super-secret-value');
        $response->assertSee('••••alue');
    }
}

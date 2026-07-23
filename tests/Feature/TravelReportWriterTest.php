<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\AnthropicConfig;
use App\Support\OpenAiConfig;
use App\Support\TravelReportWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TravelReportWriterTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_to_openai_when_no_provider_is_set(): void
    {
        $this->assertSame('openai', TravelReportWriter::provider());
    }

    public function test_falls_back_to_openai_for_an_invalid_stored_value(): void
    {
        Setting::set('report_ai_provider', 'not-a-real-provider');

        $this->assertSame('openai', TravelReportWriter::provider());
    }

    public function test_uses_claude_when_selected(): void
    {
        Setting::set('report_ai_provider', 'claude');

        $this->assertSame('claude', TravelReportWriter::provider());
    }

    public function test_is_configured_checks_the_selected_providers_key(): void
    {
        // The real .env may hold a live OPENAI_API_KEY for local dev use, so the
        // "not configured" cases below force both env fallbacks off explicitly.
        config(['services.openai.key' => null, 'services.anthropic.key' => null]);

        Setting::set('report_ai_provider', 'claude');
        $this->assertFalse(TravelReportWriter::isConfigured());

        AnthropicConfig::store('sk-ant-test');
        $this->assertTrue(TravelReportWriter::isConfigured());

        Setting::set('report_ai_provider', 'openai');
        $this->assertFalse(TravelReportWriter::isConfigured());

        OpenAiConfig::store('sk-test');
        $this->assertTrue(TravelReportWriter::isConfigured());
    }

    public function test_draft_routes_to_claude_when_selected(): void
    {
        Setting::set('report_ai_provider', 'claude');
        AnthropicConfig::store('sk-ant-test');

        Http::fake([
            'api.anthropic.com/v1/messages' => Http::response([
                'content' => [['type' => 'text', 'text' => '"title":"Von Claude","excerpt":"x","content":"<p>x</p>"}']],
                'usage' => ['input_tokens' => 10, 'output_tokens' => 20],
            ]),
            'api.openai.com/*' => Http::response([], 500),
        ]);

        $draft = TravelReportWriter::draft('Südtirol');

        $this->assertSame('Von Claude', $draft['title']);
    }

    public function test_draft_routes_to_openai_by_default(): void
    {
        OpenAiConfig::store('sk-test');

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => json_encode(['title' => 'Von OpenAI', 'excerpt' => 'x', 'content' => '<p>x</p>'])]]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30],
            ]),
            'api.anthropic.com/*' => Http::response([], 500),
        ]);

        $draft = TravelReportWriter::draft('Südtirol');

        $this->assertSame('Von OpenAI', $draft['title']);
    }
}

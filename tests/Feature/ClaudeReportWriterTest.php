<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Support\ClaudeReportWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ClaudeReportWriterTest extends TestCase
{
    use RefreshDatabase;

    private function fakeApiKey(): void
    {
        config(['services.anthropic.key' => 'sk-ant-test-key']);
    }

    public function test_throws_when_not_configured(): void
    {
        $this->expectException(RuntimeException::class);

        ClaudeReportWriter::write('Südtirol');
    }

    public function test_write_returns_the_text_content_block(): void
    {
        $this->fakeApiKey();
        Http::fake(['api.anthropic.com/v1/messages' => Http::response([
            'content' => [['type' => 'text', 'text' => '<h2>Anreise</h2><p>Mit dem Zug oder Auto erreichbar.</p>']],
            'usage' => ['input_tokens' => 500, 'output_tokens' => 1200],
        ])]);

        $content = ClaudeReportWriter::write('Südtirol');

        $this->assertStringContainsString('<h2>Anreise</h2>', $content);
        $this->assertDatabaseHas('ai_usage_logs', [
            'feature' => 'report_write', 'prompt_tokens' => 500, 'completion_tokens' => 1200, 'total_tokens' => 1700,
        ]);
    }

    public function test_draft_stitches_the_prefilled_brace_back_onto_the_response(): void
    {
        $this->fakeApiKey();
        $body = json_encode([
            'title' => 'Südtirol entdecken',
            'excerpt' => 'Kurzer Teaser',
            'content' => '<p>Ein Absatz.</p>',
            'faq' => [['question' => 'Wie kommt man hin?', 'answer' => 'Mit dem Auto oder Zug.']],
        ]);
        // Claude continues from the prefilled "{" - its response never repeats the brace.
        $bodyWithoutLeadingBrace = substr($body, 1);

        Http::fake(['api.anthropic.com/v1/messages' => Http::response([
            'content' => [['type' => 'text', 'text' => $bodyWithoutLeadingBrace]],
            'usage' => ['input_tokens' => 400, 'output_tokens' => 900],
        ])]);

        $draft = ClaudeReportWriter::draft('Südtirol');

        $this->assertSame('Südtirol entdecken', $draft['title']);
        $this->assertSame('Wie kommt man hin?', $draft['faq'][0]['question']);
        $this->assertDatabaseHas('ai_usage_logs', ['feature' => 'report_draft', 'total_tokens' => 1300]);
    }

    public function test_draft_throws_when_response_is_not_valid_json(): void
    {
        $this->fakeApiKey();
        Http::fake(['api.anthropic.com/v1/messages' => Http::response([
            'content' => [['type' => 'text', 'text' => 'kein json']],
            'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
        ])]);

        $this->expectException(RuntimeException::class);

        ClaudeReportWriter::draft('Südtirol');
    }

    public function test_throws_when_claude_request_fails(): void
    {
        $this->fakeApiKey();
        Http::fake(['api.anthropic.com/v1/messages' => Http::response(['error' => ['message' => 'overloaded']], 529)]);

        $this->expectException(RequestException::class);

        ClaudeReportWriter::write('Südtirol');
    }
}

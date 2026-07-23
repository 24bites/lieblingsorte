<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Support\OpenAiSocialCopywriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class OpenAiSocialCopywriterTest extends TestCase
{
    use RefreshDatabase;

    private function fakeApiKey(): void
    {
        config(['services.openai.key' => 'test-key']);
    }

    private function fakeChatResponse(string $text): array
    {
        return [
            'choices' => [['message' => ['content' => $text]]],
            'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 40, 'total_tokens' => 160],
        ];
    }

    public function test_writes_a_caption_for_each_platform(): void
    {
        $this->fakeApiKey();
        Http::fake(['api.openai.com/v1/chat/completions' => Http::response($this->fakeChatResponse('Schöne Toskana, jetzt entdecken. #toskana'))]);

        foreach (['pinterest', 'facebook', 'x', 'telegram', 'whatsapp'] as $platform) {
            $caption = OpenAiSocialCopywriter::write($platform, ['title' => 'Toskana', 'description' => 'Kurz']);
            $this->assertNotEmpty($caption);
        }

        $this->assertSame(5, AiUsageLog::where('feature', 'social_caption')->count());
    }

    public function test_throws_for_unknown_platform(): void
    {
        $this->fakeApiKey();

        $this->expectException(InvalidArgumentException::class);

        OpenAiSocialCopywriter::write('instagram', ['title' => 'Toskana', 'description' => 'Kurz']);
    }

    public function test_throws_when_not_configured(): void
    {
        config(['services.openai.key' => null]);

        $this->expectException(RuntimeException::class);

        OpenAiSocialCopywriter::write('facebook', ['title' => 'Toskana', 'description' => 'Kurz']);
    }

    public function test_throws_when_openai_request_fails(): void
    {
        $this->fakeApiKey();
        Http::fake(['api.openai.com/v1/chat/completions' => Http::response(['error' => ['message' => 'rate limited']], 429)]);

        // ->retry() throws its own RequestException once retries are exhausted,
        // before the write()-caught RuntimeException path is ever reached -
        // the same pre-existing behavior as OpenAiRegionDrafter/OpenAiReportWriter.
        // Callers all use catch (Throwable), so this is caught fine in practice.
        $this->expectException(RequestException::class);

        OpenAiSocialCopywriter::write('facebook', ['title' => 'Toskana', 'description' => 'Kurz']);
    }
}

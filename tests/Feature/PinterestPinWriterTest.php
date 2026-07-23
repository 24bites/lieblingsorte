<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Support\PinterestPinWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class PinterestPinWriterTest extends TestCase
{
    use RefreshDatabase;

    private function fakeApiKey(): void
    {
        config(['services.openai.key' => 'test-key']);
    }

    private function fakeChatResponse(array $brief): array
    {
        return [
            'model' => 'gpt-4o-mini',
            'choices' => [['message' => ['content' => json_encode($brief)]]],
            'usage' => ['prompt_tokens' => 150, 'completion_tokens' => 90, 'total_tokens' => 240],
        ];
    }

    private function validBrief(): array
    {
        return [
            'overlay_headline' => 'Kalterer See',
            'overlay_subline' => 'Geheimtipp fuer Familien',
            'pin_title' => 'Kalterer See Suedtirol: Der schoenste Badesee fuer Familien',
            'pin_description' => 'Der Kalterer See ist ein idealer Badesee in Suedtirol fuer Familien mit Kindern. '
                .'Flaches Wasser, warme Temperaturen und viele Liegewiesen machen ihn zum perfekten Ausflugsziel. '
                .'#suedtirol #kalterersee #familienausflug',
        ];
    }

    public function test_writes_a_pin_brief_for_each_angle(): void
    {
        $this->fakeApiKey();
        Http::fake(['api.openai.com/v1/chat/completions' => Http::response($this->fakeChatResponse($this->validBrief()))]);

        foreach (array_keys(PinterestPinWriter::ANGLES) as $angle) {
            $brief = PinterestPinWriter::write($angle, ['title' => 'Kalterer See', 'description' => 'Ein See in Südtirol']);

            $this->assertSame('Kalterer See', $brief['overlay_headline']);
            $this->assertSame('Geheimtipp fuer Familien', $brief['overlay_subline']);
            $this->assertNotEmpty($brief['pin_title']);
            $this->assertNotEmpty($brief['pin_description']);
        }

        $this->assertSame(3, AiUsageLog::where('feature', 'pinterest_pin_copy')->count());
    }

    public function test_overlay_subline_can_be_null(): void
    {
        $this->fakeApiKey();
        $brief = $this->validBrief();
        $brief['overlay_subline'] = null;
        Http::fake(['api.openai.com/v1/chat/completions' => Http::response($this->fakeChatResponse($brief))]);

        $result = PinterestPinWriter::write('allgemein', ['title' => 'Kalterer See', 'description' => 'x']);

        $this->assertNull($result['overlay_subline']);
    }

    public function test_throws_for_unknown_angle(): void
    {
        $this->fakeApiKey();

        $this->expectException(InvalidArgumentException::class);

        PinterestPinWriter::write('unbekannt', ['title' => 'Kalterer See', 'description' => 'x']);
    }

    public function test_throws_when_not_configured(): void
    {
        config(['services.openai.key' => null]);

        $this->expectException(RuntimeException::class);

        PinterestPinWriter::write('allgemein', ['title' => 'Kalterer See', 'description' => 'x']);
    }

    public function test_throws_when_response_is_not_valid_json(): void
    {
        $this->fakeApiKey();
        Http::fake(['api.openai.com/v1/chat/completions' => Http::response([
            'model' => 'gpt-4o-mini',
            'choices' => [['message' => ['content' => 'kein json']]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15],
        ])]);

        $this->expectException(RuntimeException::class);

        PinterestPinWriter::write('allgemein', ['title' => 'Kalterer See', 'description' => 'x']);
    }

    public function test_throws_when_openai_request_fails(): void
    {
        $this->fakeApiKey();
        Http::fake(['api.openai.com/v1/chat/completions' => Http::response(['error' => ['message' => 'rate limited']], 429)]);

        $this->expectException(RequestException::class);

        PinterestPinWriter::write('allgemein', ['title' => 'Kalterer See', 'description' => 'x']);
    }
}

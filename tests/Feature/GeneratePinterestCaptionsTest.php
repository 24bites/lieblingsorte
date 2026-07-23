<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\SocialPost;
use App\Support\AiCronSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeneratePinterestCaptionsTest extends TestCase
{
    use RefreshDatabase;

    private function fakeApiKey(): void
    {
        config(['services.openai.key' => 'test-key']);
    }

    private function fakeChatResponse(string $text): array
    {
        return ['choices' => [['message' => ['content' => $text]]]];
    }

    private function region(array $overrides = []): Region
    {
        return Region::create(array_merge([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'x', 'description' => 'y', 'is_published' => true,
        ], $overrides));
    }

    public function test_skips_when_cron_is_disabled_in_settings(): void
    {
        $this->fakeApiKey();
        AiCronSettings::setEnabled(AiCronSettings::PINTEREST_CAPTIONS, false);
        $this->region();
        Http::fake();

        $this->artisan('social:pinterest-captions')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(0, SocialPost::count());
    }

    public function test_skips_when_openai_is_not_configured(): void
    {
        config(['services.openai.key' => null]);
        $this->region();
        Http::fake();

        $this->artisan('social:pinterest-captions')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(0, SocialPost::count());
    }

    public function test_generates_a_caption_for_a_feed_eligible_region_without_one(): void
    {
        $this->fakeApiKey();
        $region = $this->region();
        Http::fake(['api.openai.com/*' => Http::response($this->fakeChatResponse('📍 Toskana entdecken #Reisetipps'))]);

        $this->artisan('social:pinterest-captions')->assertSuccessful();

        $post = $region->socialPosts()->where('platform', 'pinterest')->first();
        $this->assertNotNull($post);
        $this->assertSame('📍 Toskana entdecken #Reisetipps', $post->caption);
    }

    public function test_does_not_regenerate_a_caption_that_already_exists(): void
    {
        $this->fakeApiKey();
        $region = $this->region();
        $region->socialPosts()->create(['platform' => 'pinterest', 'caption' => 'Bestehender Text', 'link_url' => 'https://24bites.de/toskana']);
        Http::fake(['api.openai.com/*' => Http::response($this->fakeChatResponse('Neuer Text'))]);

        $this->artisan('social:pinterest-captions')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame('Bestehender Text', $region->socialPosts()->where('platform', 'pinterest')->value('caption'));
    }

    public function test_respects_the_limit_option(): void
    {
        $this->fakeApiKey();
        for ($i = 1; $i <= 3; $i++) {
            $this->region(['name' => "Region {$i}"]);
        }
        Http::fake(['api.openai.com/*' => Http::response($this->fakeChatResponse('Text'))]);

        $this->artisan('social:pinterest-captions', ['--limit' => 2])->assertSuccessful();

        $this->assertSame(2, SocialPost::where('platform', 'pinterest')->count());
    }

    public function test_a_failed_generation_does_not_stop_the_rest_of_the_run(): void
    {
        $this->fakeApiKey();
        $succeeding = $this->region(['name' => 'Erfolg']);
        $this->travel(1)->minutes();
        $failing = $this->region(['name' => 'Fehlschlag']);
        // OpenAiSocialCopywriter::write() uses ->retry(2, 1000) - 3 total
        // attempts - so 3 failures are needed to genuinely exhaust retries
        // for the first (newer, processed-first) item before the second
        // item's call gets the success response.
        Http::fake([
            'api.openai.com/*' => Http::sequence()
                ->push(['error' => ['message' => 'rate limited']], 429)
                ->push(['error' => ['message' => 'rate limited']], 429)
                ->push(['error' => ['message' => 'rate limited']], 429)
                ->push($this->fakeChatResponse('Text für Erfolg')),
        ]);

        $this->artisan('social:pinterest-captions')->assertSuccessful();

        $this->assertNull($failing->socialPosts()->where('platform', 'pinterest')->first());
        $this->assertNotNull($succeeding->socialPosts()->where('platform', 'pinterest')->first());
    }
}

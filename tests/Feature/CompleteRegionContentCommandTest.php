<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Region;
use App\Models\TravelTip;
use App\Support\AiCronSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompleteRegionContentCommandTest extends TestCase
{
    use RefreshDatabase;

    private function fakeApiKey(): void
    {
        config(['services.openai.key' => 'test-key']);
    }

    private function fakeImageResponse(): array
    {
        return ['data' => [['b64_json' => base64_encode('fake-png-bytes')]]];
    }

    private function fakeTipResponse(string $title = 'Neuer Tipp'): array
    {
        return [
            'choices' => [
                ['message' => ['content' => json_encode([
                    'title' => $title,
                    'short_description' => 'Kurz',
                    'description' => 'Lang',
                ])]],
            ],
        ];
    }

    private function approvedAiRegion(int $tipCount = 0): Region
    {
        $region = Region::create([
            'name' => 'Kotor', 'type' => 'Stadt', 'country' => 'Montenegro',
            'short_description' => 'Kurz', 'description' => 'Lang',
            'is_published' => false, 'ai_generated' => true, 'approved_at' => now(),
        ]);

        for ($i = 0; $i < $tipCount; $i++) {
            TravelTip::create([
                'region_id' => $region->id, 'title' => "Tipp {$i}",
                'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => false,
            ]);
        }

        return $region;
    }

    private function manualRegion(int $tipCount = 0): Region
    {
        $region = Region::create([
            'name' => 'Neustadt', 'type' => 'Stadt', 'country' => 'Deutschland',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => false,
        ]);

        for ($i = 0; $i < $tipCount; $i++) {
            TravelTip::create([
                'region_id' => $region->id, 'title' => "Tipp {$i}",
                'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => false,
            ]);
        }

        return $region;
    }

    public function test_generates_cover_image_first_when_missing(): void
    {
        Storage::fake('public');
        $this->fakeApiKey();
        Http::fake(['api.openai.com/v1/images/generations' => Http::response($this->fakeImageResponse())]);
        $region = $this->approvedAiRegion(12);

        $this->artisan('regions:complete-content', ['--steps' => 1])->assertSuccessful();

        $region->refresh();
        $this->assertTrue($region->media()->where('is_cover', true)->exists());
        $this->assertNotNull($region->hero_image);
        $this->assertFalse($region->is_published);
    }

    public function test_drafts_additional_tips_until_target_reached(): void
    {
        $this->fakeApiKey();
        Http::fake(['api.openai.com/v1/chat/completions' => Http::response($this->fakeTipResponse())]);
        $region = $this->approvedAiRegion(9);
        $region->media()->create(['file_path' => 'regions/kotor/a.jpg', 'alt_text' => 'a', 'sort_order' => 0, 'is_cover' => true]);

        $this->artisan('regions:complete-content', ['--steps' => 3])->assertSuccessful();

        $this->assertSame(12, $region->travelTips()->count());
    }

    public function test_generates_images_for_tips_without_one(): void
    {
        Storage::fake('public');
        $this->fakeApiKey();
        Http::fake(['api.openai.com/v1/images/generations' => Http::response($this->fakeImageResponse())]);
        $region = $this->approvedAiRegion(12);
        $region->media()->create(['file_path' => 'regions/kotor/a.jpg', 'alt_text' => 'a', 'sort_order' => 0, 'is_cover' => true]);

        $this->artisan('regions:complete-content', ['--steps' => 1])->assertSuccessful();

        $this->assertSame(1, Media::where('mediable_type', TravelTip::class)->count());
    }

    public function test_publishes_region_and_tips_once_everything_is_ready(): void
    {
        $region = $this->approvedAiRegion(12);
        $region->media()->create(['file_path' => 'regions/kotor/a.jpg', 'alt_text' => 'a', 'sort_order' => 0, 'is_cover' => true]);
        foreach ($region->travelTips as $tip) {
            $tip->media()->create(['file_path' => "tips/{$tip->slug}/a.jpg", 'alt_text' => 'a', 'sort_order' => 0, 'is_cover' => true]);
        }

        $this->artisan('regions:complete-content', ['--steps' => 1])->assertSuccessful();

        $region->refresh();
        $this->assertTrue($region->is_published);
        $this->assertNotNull($region->content_completed_at);
        $this->assertSame(12, $region->travelTips()->where('is_published', true)->count());
    }

    public function test_manually_created_region_is_eligible_without_approval(): void
    {
        Storage::fake('public');
        $this->fakeApiKey();
        Http::fake(['api.openai.com/v1/images/generations' => Http::response($this->fakeImageResponse())]);
        $region = $this->manualRegion(12);

        $this->artisan('regions:complete-content', ['--steps' => 1])->assertSuccessful();

        $this->assertTrue($region->fresh()->media()->where('is_cover', true)->exists());
    }

    public function test_pending_ai_region_without_approval_is_not_touched(): void
    {
        Storage::fake('public');
        $this->fakeApiKey();
        Http::fake(['api.openai.com/v1/images/generations' => Http::response($this->fakeImageResponse())]);
        $region = Region::create([
            'name' => 'Kotor', 'type' => 'Stadt', 'country' => 'Montenegro',
            'short_description' => 'Kurz', 'description' => 'Lang',
            'is_published' => false, 'ai_generated' => true,
        ]);

        $this->artisan('regions:complete-content', ['--steps' => 5])->assertSuccessful();

        $this->assertFalse($region->fresh()->media()->exists());
        Http::assertNothingSent();
    }

    public function test_rejected_region_is_never_processed(): void
    {
        Storage::fake('public');
        $this->fakeApiKey();
        Http::fake(['api.openai.com/v1/images/generations' => Http::response($this->fakeImageResponse())]);
        $region = $this->approvedAiRegion(12);
        $region->update(['rejected_at' => now()]);

        $this->artisan('regions:complete-content', ['--steps' => 5])->assertSuccessful();

        $this->assertFalse($region->fresh()->media()->exists());
    }

    public function test_already_completed_region_is_never_touched_again(): void
    {
        Storage::fake('public');
        $this->fakeApiKey();
        Http::fake(['api.openai.com/v1/images/generations' => Http::response($this->fakeImageResponse())]);
        $region = $this->manualRegion(12);
        $region->update(['content_completed_at' => now()]);

        $this->artisan('regions:complete-content', ['--steps' => 5])->assertSuccessful();

        $this->assertFalse($region->fresh()->media()->exists());
        Http::assertNothingSent();
    }

    public function test_disabled_switch_skips_the_command(): void
    {
        Storage::fake('public');
        $this->fakeApiKey();
        AiCronSettings::setEnabled(AiCronSettings::REGIONS_COMPLETE_CONTENT, false);
        Http::fake();
        $this->approvedAiRegion(12);

        $this->artisan('regions:complete-content')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_does_nothing_when_openai_is_not_configured(): void
    {
        // Isolate from a real OPENAI_API_KEY possibly set in .env for local
        // use - this test only cares about the not-configured code path.
        config(['services.openai.key' => null]);
        Http::fake();
        $this->approvedAiRegion(12);

        $this->artisan('regions:complete-content')->assertSuccessful();

        $this->assertSame(0, Media::count());
        Http::assertNothingSent();
    }

    public function test_a_failing_region_does_not_block_other_eligible_regions(): void
    {
        Storage::fake('public');
        $this->fakeApiKey();
        $failing = $this->manualRegion(12);
        $healthy = $this->approvedAiRegion(12);

        // Key the fake response off which region's prompt is being requested
        // (AiImagePromptBuilder embeds the region name) rather than call
        // order, since both regions hit the same endpoint.
        Http::fake([
            'api.openai.com/v1/images/generations' => function ($request) use ($failing) {
                return str_contains($request->body(), $failing->name)
                    ? Http::response(['error' => ['message' => 'boom']], 500)
                    : Http::response($this->fakeImageResponse());
            },
        ]);

        $this->artisan('regions:complete-content', ['--steps' => 2])->assertSuccessful();

        $this->assertFalse($failing->fresh()->media()->where('is_cover', true)->exists());
        $this->assertTrue($healthy->fresh()->media()->where('is_cover', true)->exists());
    }

    public function test_steps_option_limits_work_done_per_run(): void
    {
        $this->fakeApiKey();
        Http::fake(['api.openai.com/v1/chat/completions' => Http::response($this->fakeTipResponse())]);
        $region = $this->approvedAiRegion(0);
        $region->media()->create(['file_path' => 'regions/kotor/a.jpg', 'alt_text' => 'a', 'sort_order' => 0, 'is_cover' => true]);

        $this->artisan('regions:complete-content', ['--steps' => 3])->assertSuccessful();

        $this->assertSame(3, $region->travelTips()->count());
    }
}

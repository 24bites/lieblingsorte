<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Region;
use App\Models\TravelTip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImagesAiReplaceCommandTest extends TestCase
{
    use RefreshDatabase;

    private function fakeApiKey(): void
    {
        config(['services.openai.key' => 'test-key']);
    }

    private function fakeOpenAiImages(): void
    {
        $fakeImage = base64_encode('fake-png-bytes');
        Http::fake([
            'api.openai.com/v1/images/generations' => Http::response([
                'data' => [['b64_json' => $fakeImage]],
            ], 200),
        ]);
    }

    private function region(string $name = 'Toskana'): Region
    {
        return Region::create([
            'name' => $name, 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => true,
        ]);
    }

    private function attachMedia(Region|TravelTip $model, string $path, string $source, bool $isCover = false): Media
    {
        Storage::disk('public')->put($path, 'original-bytes');

        return $model->media()->create([
            'file_path' => $path,
            'alt_text' => 'Alt',
            'sort_order' => 0,
            'is_cover' => $isCover,
            'source' => $source,
        ]);
    }

    public function test_replaces_wikimedia_and_generated_media_with_ai_images(): void
    {
        Storage::fake('public');
        $this->fakeApiKey();
        $this->fakeOpenAiImages();

        $region = $this->region();
        $media = $this->attachMedia($region, 'regions/toskana/toskana-1.jpg', 'wikimedia', isCover: true);
        $region->update(['hero_image' => $media->file_path]);

        $tip = TravelTip::create([
            'region_id' => $region->id, 'title' => 'Piazza del Campo',
            'short_description' => 'Muschelförmiger Platz', 'description' => 'Lang',
        ]);
        $tipMedia = $this->attachMedia($tip, 'tips/piazza-del-campo/piazza-del-campo-1.jpg', 'generated');

        $this->artisan('images:ai-replace')->assertSuccessful();

        $media->refresh();
        $tipMedia->refresh();
        $region->refresh();

        $this->assertSame('ai', $media->source);
        $this->assertNotSame('regions/toskana/toskana-1.jpg', $media->file_path);
        $this->assertSame($media->file_path, $region->hero_image);
        Storage::disk('public')->assertMissing('regions/toskana/toskana-1.jpg');
        Storage::disk('public')->assertExists($media->file_path);

        $this->assertSame('ai', $tipMedia->source);
        $this->assertNotSame('tips/piazza-del-campo/piazza-del-campo-1.jpg', $tipMedia->file_path);
    }

    public function test_upload_source_media_is_never_touched(): void
    {
        Storage::fake('public');
        $this->fakeApiKey();
        $this->fakeOpenAiImages();

        $region = $this->region();
        $media = $this->attachMedia($region, 'regions/toskana/toskana-1.jpg', 'upload');

        $this->artisan('images:ai-replace')->assertSuccessful();

        $media->refresh();
        $this->assertSame('upload', $media->source);
        $this->assertSame('regions/toskana/toskana-1.jpg', $media->file_path);
    }

    public function test_limit_option_caps_number_of_replacements_per_run(): void
    {
        Storage::fake('public');
        $this->fakeApiKey();
        $this->fakeOpenAiImages();

        $region = $this->region();
        for ($i = 1; $i <= 7; $i++) {
            $this->attachMedia($region, "regions/toskana/toskana-{$i}.jpg", 'wikimedia');
        }

        $this->artisan('images:ai-replace', ['--limit' => 5])->assertSuccessful();

        $this->assertSame(5, Media::where('source', 'ai')->count());
        $this->assertSame(2, Media::where('source', 'wikimedia')->count());
    }

    public function test_does_nothing_when_openai_is_not_configured(): void
    {
        Storage::fake('public');
        config(['services.openai.key' => null]);

        $region = $this->region();
        $media = $this->attachMedia($region, 'regions/toskana/toskana-1.jpg', 'wikimedia');

        $this->artisan('images:ai-replace')->assertSuccessful();

        $media->refresh();
        $this->assertSame('wikimedia', $media->source);
    }
}

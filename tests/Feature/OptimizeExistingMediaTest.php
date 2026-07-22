<?php

namespace Tests\Feature;

use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OptimizeExistingMediaTest extends TestCase
{
    use RefreshDatabase;

    private function region(): Region
    {
        return Region::create([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'x', 'description' => 'y', 'is_published' => true,
        ]);
    }

    public function test_backfill_generates_optimized_path_for_media_missing_one(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('old.jpg', 3000, 2000)->storeAs('regions/toskana', 'old.jpg', 'public');
        $media = $this->region()->media()->create([
            'file_path' => $path, 'alt_text' => 'Toskana', 'sort_order' => 0, 'is_cover' => true,
        ]);

        $this->artisan('images:optimize')->assertSuccessful();

        $this->assertNotNull($media->fresh()->optimized_path);
        Storage::disk('public')->assertExists($media->fresh()->optimized_path);
    }

    public function test_backfill_skips_media_that_already_has_an_optimized_path(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('done.jpg', 800, 600)->storeAs('regions/toskana', 'done.jpg', 'public');
        $media = $this->region()->media()->create([
            'file_path' => $path, 'optimized_path' => 'regions/toskana/done-web.jpg',
            'alt_text' => 'Toskana', 'sort_order' => 0, 'is_cover' => true,
        ]);

        $this->artisan('images:optimize')->assertSuccessful();

        $this->assertSame('regions/toskana/done-web.jpg', $media->fresh()->optimized_path);
    }

    public function test_backfill_reports_success_when_nothing_to_do(): void
    {
        $this->artisan('images:optimize')->assertSuccessful();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportMediaCreditsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function region(): Region
    {
        return Region::create([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => true,
        ]);
    }

    private ?string $originalCreditsJson = null;

    protected function setUp(): void
    {
        parent::setUp();

        $path = storage_path('app/credits.json');
        $this->originalCreditsJson = file_exists($path) ? file_get_contents($path) : null;
    }

    protected function tearDown(): void
    {
        $path = storage_path('app/credits.json');

        if ($this->originalCreditsJson === null) {
            @unlink($path);
        } else {
            file_put_contents($path, $this->originalCreditsJson);
        }

        parent::tearDown();
    }

    private function writeCreditsJson(array $credits): string
    {
        $path = storage_path('app/credits.json');
        file_put_contents($path, json_encode($credits));

        return $path;
    }

    public function test_imports_matching_credit_into_media_row(): void
    {
        $region = $this->region();
        $media = $region->media()->create([
            'file_path' => 'regions/toskana/toskana-1.jpg', 'alt_text' => 'Toskana',
            'sort_order' => 0, 'is_cover' => true, 'source' => 'wikimedia',
        ]);
        $this->writeCreditsJson([[
            'used_for' => 'Toskana',
            'file' => 'regions/toskana/toskana-1.jpg',
            'source_title' => 'File:Toskana.jpg',
            'author' => 'Max Mustermann',
            'license' => 'CC BY-SA 4.0',
            'source_url' => 'https://commons.wikimedia.org/wiki/File:Toskana.jpg',
        ]]);

        $this->artisan('media:import-credits')->assertSuccessful();

        $media->refresh();
        $this->assertSame('Max Mustermann', $media->credit_author);
        $this->assertSame('CC BY-SA 4.0', $media->credit_license);
        $this->assertSame('https://commons.wikimedia.org/wiki/File:Toskana.jpg', $media->credit_source_url);
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $region = $this->region();
        $media = $region->media()->create([
            'file_path' => 'regions/toskana/toskana-1.jpg', 'alt_text' => 'Toskana',
            'sort_order' => 0, 'is_cover' => true, 'source' => 'wikimedia',
        ]);
        $this->writeCreditsJson([[
            'used_for' => 'Toskana', 'file' => 'regions/toskana/toskana-1.jpg',
            'source_title' => 'File:Toskana.jpg', 'author' => 'Max Mustermann',
            'license' => 'CC BY-SA 4.0', 'source_url' => 'https://commons.wikimedia.org/wiki/File:Toskana.jpg',
        ]]);

        $this->artisan('media:import-credits', ['--dry-run' => true])->assertSuccessful();

        $this->assertNull($media->fresh()->credit_author);
    }

    public function test_skips_media_that_already_has_a_credit(): void
    {
        $region = $this->region();
        $media = $region->media()->create([
            'file_path' => 'regions/toskana/toskana-1.jpg', 'alt_text' => 'Toskana',
            'sort_order' => 0, 'is_cover' => true, 'source' => 'wikimedia',
            'credit_author' => 'Bereits gesetzt',
        ]);
        $this->writeCreditsJson([[
            'used_for' => 'Toskana', 'file' => 'regions/toskana/toskana-1.jpg',
            'source_title' => 'File:Toskana.jpg', 'author' => 'Anderer Autor',
            'license' => 'CC BY-SA 4.0', 'source_url' => 'https://commons.wikimedia.org/wiki/File:Toskana.jpg',
        ]]);

        $this->artisan('media:import-credits')->assertSuccessful();

        $this->assertSame('Bereits gesetzt', $media->fresh()->credit_author);
    }

    public function test_truncates_overly_long_author_field(): void
    {
        $region = $this->region();
        $media = $region->media()->create([
            'file_path' => 'regions/toskana/toskana-1.jpg', 'alt_text' => 'Toskana',
            'sort_order' => 0, 'is_cover' => true, 'source' => 'wikimedia',
        ]);
        $this->writeCreditsJson([[
            'used_for' => 'Toskana', 'file' => 'regions/toskana/toskana-1.jpg',
            'source_title' => 'File:Toskana.jpg', 'author' => str_repeat('X', 500),
            'license' => 'CC BY-SA 4.0', 'source_url' => 'https://commons.wikimedia.org/wiki/File:Toskana.jpg',
        ]]);

        $this->artisan('media:import-credits')->assertSuccessful();

        $this->assertSame(300, mb_strlen($media->fresh()->credit_author));
    }

    public function test_handles_missing_credits_file_gracefully(): void
    {
        @unlink(storage_path('app/credits.json'));

        $this->artisan('media:import-credits')->assertSuccessful();
    }
}

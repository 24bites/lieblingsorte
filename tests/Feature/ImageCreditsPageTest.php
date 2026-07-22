<?php

namespace Tests\Feature;

use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageCreditsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_bildquellen_page_loads_successfully(): void
    {
        $response = $this->get('/bildquellen');

        $response->assertOk();
        $response->assertSee('Bildquellen');
    }

    public function test_impressum_links_to_bildquellen_page_instead_of_credits_md(): void
    {
        $response = $this->get('/impressum');

        $response->assertOk();
        $response->assertSee(route('legal.bildquellen'), false);
        $response->assertDontSee('CREDITS.md');
    }

    public function test_bildquellen_page_shows_empty_state_without_any_credited_media(): void
    {
        $response = $this->get('/bildquellen');

        $response->assertOk();
        $response->assertSee('Aktuell sind keine Bildquellen hinterlegt');
    }

    public function test_bildquellen_page_lists_media_with_credit_fields(): void
    {
        Storage::fake('public');
        $region = Region::create([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => true,
        ]);
        Storage::disk('public')->put('regions/toskana/toskana-1.jpg', 'fake-bytes');
        $region->media()->create([
            'file_path' => 'regions/toskana/toskana-1.jpg',
            'alt_text' => 'Toskana',
            'sort_order' => 0,
            'is_cover' => true,
            'source' => 'wikimedia',
            'credit_author' => 'Max Mustermann',
            'credit_license' => 'CC BY-SA 4.0',
            'credit_source_title' => 'File:Toskana.jpg',
            'credit_source_url' => 'https://commons.wikimedia.org/wiki/File:Toskana.jpg',
        ]);

        $response = $this->get('/bildquellen');

        $response->assertOk();
        $response->assertSee('Toskana');
        $response->assertSee('Max Mustermann');
        $response->assertSee('CC BY-SA 4.0');
        $response->assertSee('https://commons.wikimedia.org/wiki/File:Toskana.jpg', false);
    }

    public function test_bildquellen_page_excludes_media_without_any_credit_fields(): void
    {
        $region = Region::create([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => true,
        ]);
        $region->media()->create([
            'file_path' => 'regions/toskana/toskana-1.jpg',
            'alt_text' => 'Toskana',
            'sort_order' => 0,
            'is_cover' => true,
            'source' => 'generated',
        ]);

        $response = $this->get('/bildquellen');

        $response->assertOk();
        $response->assertSee('Aktuell sind keine Bildquellen hinterlegt');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PinterestFeedTest extends TestCase
{
    use RefreshDatabase;

    private function region(array $overrides = []): Region
    {
        return Region::create(array_merge([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Sonnige Hügel und Zypressenalleen', 'description' => 'Lang',
            'is_published' => true,
        ], $overrides));
    }

    public function test_feed_responds_with_rss_content_type(): void
    {
        $this->region();

        $response = $this->get(route('pinterest-feed'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function test_feed_is_valid_xml(): void
    {
        $this->region();

        $xml = $this->get(route('pinterest-feed'))->getContent();

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $isValid = $dom->loadXML($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        $this->assertTrue($isValid, 'Feed is not well-formed XML: '.json_encode($errors));
    }

    public function test_feed_includes_region_teaser_as_description(): void
    {
        $this->region();

        $xml = $this->get(route('pinterest-feed'))->getContent();

        $this->assertStringContainsString('Sonnige Hügel und Zypressenalleen', $xml);
    }

    public function test_feed_includes_cover_image_as_enclosure(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('regions/toskana/toskana-1.jpg', 'fake-bytes');
        $region = $this->region();
        $region->media()->create([
            'file_path' => 'regions/toskana/toskana-1.jpg', 'alt_text' => 'Toskana',
            'sort_order' => 0, 'is_cover' => true,
        ]);

        $xml = $this->get(route('pinterest-feed'))->getContent();

        $this->assertStringContainsString('<enclosure', $xml);
        $this->assertStringContainsString('toskana-1.jpg', $xml);
        $this->assertStringContainsString('type="image/jpeg"', $xml);
    }

    public function test_feed_omits_enclosure_when_no_cover_image(): void
    {
        $this->region();

        $xml = $this->get(route('pinterest-feed'))->getContent();

        $this->assertStringNotContainsString('<enclosure', $xml);
    }

    public function test_feed_excludes_unpublished_regions(): void
    {
        $this->region(['name' => 'Entwurf', 'is_published' => false]);

        $xml = $this->get(route('pinterest-feed'))->getContent();

        $this->assertStringNotContainsString('Entwurf', $xml);
    }

    public function test_feed_is_limited_to_25_most_recently_updated_regions(): void
    {
        for ($i = 1; $i <= 27; $i++) {
            $this->region(['name' => "Region {$i}"]);
            $this->travel(1)->minutes();
        }

        $xml = $this->get(route('pinterest-feed'))->getContent();
        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $this->assertCount(25, iterator_to_array($dom->getElementsByTagName('item')));
        $this->assertStringContainsString('Region 27', $xml);
        $this->assertStringContainsString('Region 3', $xml);
        $this->assertStringNotContainsString('Region 2<', $xml);
        $this->assertStringNotContainsString('Region 1<', $xml);
    }

    public function test_social_hub_index_shows_the_feed_url(): void
    {
        $admin = User::create([
            'name' => 'Administrator', 'email' => 'admin@lieblingsorte.test',
            'password' => Hash::make('admin123'), 'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.social-hub.index'));

        $response->assertOk();
        $response->assertSee(route('pinterest-feed'), false);
    }
}

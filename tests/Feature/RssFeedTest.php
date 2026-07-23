<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\TravelReport;
use App\Models\TravelTip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RssFeedTest extends TestCase
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

    private function tip(array $overrides = []): TravelTip
    {
        $regionId = $overrides['region_id'] ?? $this->region(['name' => 'Region-'.uniqid()])->id;

        return TravelTip::create(array_merge([
            'region_id' => $regionId,
            'title' => 'Piazza del Campo', 'short_description' => 'Herzstück von Siena', 'description' => 'Lang',
            'is_published' => true,
        ], $overrides));
    }

    private function report(array $overrides = []): TravelReport
    {
        return TravelReport::create(array_merge([
            'title' => 'Ein Wochenende auf Föhr',
            'excerpt' => 'Ein ruhiges Winterwochenende auf der Insel.',
            'content' => '<p>Text.</p>',
            'author_name' => 'Anna',
            'is_published' => true,
            'published_at' => now(),
        ], $overrides));
    }

    public function test_feed_responds_with_rss_content_type(): void
    {
        $this->region();

        $response = $this->get(route('feed'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function test_feed_is_valid_xml(): void
    {
        $this->region();
        $this->tip();
        $this->report();

        $xml = $this->get(route('feed'))->getContent();

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $isValid = $dom->loadXML($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        $this->assertTrue($isValid, 'Feed is not well-formed XML: '.json_encode($errors));
    }

    public function test_feed_includes_all_three_content_types(): void
    {
        $this->region(['name' => 'Toskana']);
        $this->tip(['title' => 'Piazza del Campo']);
        $this->report(['title' => 'Ein Wochenende auf Föhr']);

        $xml = $this->get(route('feed'))->getContent();

        $this->assertStringContainsString('Toskana', $xml);
        $this->assertStringContainsString('Piazza del Campo', $xml);
        $this->assertStringContainsString('Ein Wochenende auf Föhr', $xml);
        $this->assertStringContainsString('<category>Region</category>', $xml);
        $this->assertStringContainsString('<category>Reiseziel</category>', $xml);
        $this->assertStringContainsString('<category>Reisebericht</category>', $xml);
    }

    public function test_feed_excludes_unpublished_items(): void
    {
        $this->region(['name' => 'Entwurfsregion', 'is_published' => false]);
        $this->tip(['title' => 'Entwurfstipp', 'is_published' => false]);
        $this->report(['title' => 'Entwurfsbericht', 'is_published' => false, 'published_at' => null]);

        $xml = $this->get(route('feed'))->getContent();

        $this->assertStringNotContainsString('Entwurfsregion', $xml);
        $this->assertStringNotContainsString('Entwurfstipp', $xml);
        $this->assertStringNotContainsString('Entwurfsbericht', $xml);
    }

    public function test_feed_items_are_sorted_by_recency_across_types(): void
    {
        $tipRegion = $this->region(['name' => 'Region für Ziel', 'is_published' => false]);

        $this->region(['name' => 'Älteste Region']);
        $this->travel(1)->minutes();
        $this->report(['title' => 'Mittlerer Bericht']);
        $this->travel(1)->minutes();
        $this->tip(['title' => 'Neuestes Ziel', 'region_id' => $tipRegion->id]);

        $xml = $this->get(route('feed'))->getContent();
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $titles = [];
        foreach ($dom->getElementsByTagName('item') as $item) {
            $titles[] = $item->getElementsByTagName('title')->item(0)->textContent;
        }

        $this->assertSame(['Neuestes Ziel', 'Mittlerer Bericht', 'Älteste Region'], $titles);
    }

    public function test_feed_includes_self_referencing_atom_link(): void
    {
        $this->region();

        $xml = $this->get(route('feed'))->getContent();

        $this->assertStringContainsString('rel="self"', $xml);
        $this->assertStringContainsString(route('feed'), $xml);
    }

    public function test_feed_is_discoverable_via_link_tag_on_the_homepage(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('rel="alternate" type="application/rss+xml"', false);
        $response->assertSee(route('feed'), false);
    }

    public function test_footer_links_to_the_feed(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(route('feed'), false);
    }
}

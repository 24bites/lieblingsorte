<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\TravelTip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private function makeRegion(array $overrides = []): Region
    {
        return Region::create(array_merge([
            'name' => 'Südtirol',
            'type' => 'Region',
            'country' => 'Italien',
            'short_description' => 'Kurzbeschreibung',
            'description' => 'Ausführliche Beschreibung.',
            'is_published' => true,
        ], $overrides));
    }

    private function makeTip(Region $region, array $overrides = []): TravelTip
    {
        return $region->travelTips()->create(array_merge([
            'title' => 'Kalterer See',
            'short_description' => 'Kurzbeschreibung Tipp',
            'description' => 'Ausführliche Beschreibung Tipp.',
            'is_published' => true,
        ], $overrides));
    }

    public function test_home_page_loads(): void
    {
        $this->makeRegion();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Die besten Reisetipps');
    }

    public function test_region_page_loads_for_published_region(): void
    {
        $region = $this->makeRegion(['slug' => 'suedtirol']);

        $response = $this->get('/suedtirol');

        $response->assertOk();
        $response->assertSee($region->name);
    }

    public function test_second_region_page_loads(): void
    {
        $this->makeRegion(['name' => 'Allgäu', 'slug' => 'allgaeu', 'country' => 'Deutschland']);

        $response = $this->get('/allgaeu');

        $response->assertOk();
        $response->assertSee('Allgäu');
    }

    public function test_unpublished_region_is_not_publicly_reachable(): void
    {
        $this->makeRegion(['slug' => 'unveroeffentlicht', 'is_published' => false]);

        $response = $this->get('/unveroeffentlicht');

        $response->assertNotFound();
    }

    public function test_travel_tip_detail_page_loads(): void
    {
        $region = $this->makeRegion(['slug' => 'suedtirol']);
        $tip = $this->makeTip($region, ['slug' => 'kalterer-see']);

        $response = $this->get('/suedtirol/kalterer-see');

        $response->assertOk();
        $response->assertSee($tip->title);
    }

    public function test_unpublished_travel_tip_is_not_publicly_reachable(): void
    {
        $region = $this->makeRegion(['slug' => 'suedtirol']);
        $this->makeTip($region, ['slug' => 'geheim', 'is_published' => false]);

        $response = $this->get('/suedtirol/geheim');

        $response->assertNotFound();
    }

    public function test_travel_tip_in_unpublished_region_is_not_reachable_even_if_tip_is_published(): void
    {
        $region = $this->makeRegion(['slug' => 'versteckt', 'is_published' => false]);
        $this->makeTip($region, ['slug' => 'kalterer-see']);

        $response = $this->get('/versteckt/kalterer-see');

        $response->assertNotFound();
    }

    public function test_unknown_url_returns_404(): void
    {
        $response = $this->get('/dies-gibt-es-nicht');

        $response->assertNotFound();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Label;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAndFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_matching_region_and_tip(): void
    {
        $region = Region::create([
            'name' => 'Südtirol', 'slug' => 'suedtirol', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Dolomiten', 'description' => 'Beschreibung', 'is_published' => true,
        ]);
        $region->travelTips()->create([
            'title' => 'Kalterer See', 'slug' => 'kalterer-see',
            'short_description' => 'Der wärmste Badesee der Alpen',
            'description' => 'Beschreibung', 'is_published' => true,
        ]);

        $response = $this->get('/suche?q=see');

        $response->assertOk();
        $response->assertSee('Kalterer See');
    }

    public function test_search_with_no_matches_shows_alternatives(): void
    {
        Region::create([
            'name' => 'Südtirol', 'slug' => 'suedtirol', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Dolomiten', 'description' => 'Beschreibung', 'is_published' => true,
        ]);

        $response = $this->get('/suche?q=xyznichtvorhanden');

        $response->assertOk();
        $response->assertSee('Keine Ergebnisse');
    }

    public function test_region_label_filter_only_shows_matching_tips(): void
    {
        $region = Region::create([
            'name' => 'Südtirol', 'slug' => 'suedtirol', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Dolomiten', 'description' => 'Beschreibung', 'is_published' => true,
        ]);
        $familie = Label::create(['name' => 'Familie', 'slug' => 'familie', 'color' => '#2f6b4f']);
        Label::create(['name' => 'Geheimtipp', 'slug' => 'geheimtipp', 'color' => '#b8863b']);

        $familyTip = $region->travelTips()->create([
            'title' => 'Familienausflug', 'slug' => 'familienausflug',
            'short_description' => 'x', 'description' => 'y', 'is_published' => true,
        ]);
        $familyTip->labels()->attach($familie->id);

        $region->travelTips()->create([
            'title' => 'Anderer Tipp', 'slug' => 'anderer-tipp',
            'short_description' => 'x', 'description' => 'y', 'is_published' => true,
        ]);

        $response = $this->get('/suedtirol?label=familie');

        $response->assertOk();
        $response->assertSee('Familienausflug');
        $response->assertDontSee('Anderer Tipp');
    }

    public function test_region_free_entry_filter_only_shows_free_tips(): void
    {
        $region = Region::create([
            'name' => 'Südtirol', 'slug' => 'suedtirol', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Dolomiten', 'description' => 'Beschreibung', 'is_published' => true,
        ]);

        $region->travelTips()->create([
            'title' => 'Kostenloser Tipp', 'slug' => 'kostenloser-tipp',
            'short_description' => 'x', 'description' => 'y', 'is_published' => true, 'free_entry' => true,
        ]);
        $region->travelTips()->create([
            'title' => 'Kostenpflichtiger Tipp', 'slug' => 'kostenpflichtiger-tipp',
            'short_description' => 'x', 'description' => 'y', 'is_published' => true, 'free_entry' => false,
        ]);

        $response = $this->get('/suedtirol?kostenlos=1');

        $response->assertOk();
        $response->assertSee('Kostenloser Tipp');
        $response->assertDontSee('Kostenpflichtiger Tipp');
    }
}

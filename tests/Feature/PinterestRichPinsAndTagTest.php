<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\Setting;
use App\Models\TravelReport;
use App\Models\TravelTip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PinterestRichPinsAndTagTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Administrator', 'email' => 'admin@lieblingsorte.test',
            'password' => Hash::make('admin123'), 'role' => 'admin',
        ]);
    }

    private function validSettingsPayload(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'Lieblingsorte',
            'site_claim' => 'Claim',
            'site_description' => 'Beschreibung',
            'contact_email' => 'hallo@lieblingsorte.test',
            'images_ai_replace_interval' => 10,
            'regions_auto_generate_interval' => 10,
            'regions_complete_content_interval' => 10,
        ], $overrides);
    }

    public function test_region_page_has_article_rich_pin_meta_tags(): void
    {
        $region = Region::create([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'x', 'description' => 'y', 'is_published' => true,
        ]);

        $response = $this->get(route('regions.show', $region));

        $response->assertOk();
        $response->assertSee('property="og:type" content="article"', false);
        $response->assertSee('property="article:modified_time"', false);
    }

    public function test_travel_tip_page_has_article_rich_pin_meta_tags(): void
    {
        $region = Region::create([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'x', 'description' => 'y', 'is_published' => true,
        ]);
        $tip = TravelTip::create([
            'region_id' => $region->id, 'title' => 'Piazza del Campo',
            'short_description' => 'x', 'description' => 'y', 'is_published' => true,
        ]);

        $response = $this->get(route('tips.show', [$region, $tip]));

        $response->assertOk();
        $response->assertSee('property="og:type" content="article"', false);
        $response->assertSee('property="article:modified_time"', false);
    }

    public function test_travel_report_page_has_article_author_and_published_time(): void
    {
        $report = TravelReport::create([
            'title' => 'Ein Wochenende auf Föhr', 'excerpt' => 'Kurz', 'content' => 'Lang',
            'author_name' => 'Lena Vogt', 'is_published' => true, 'published_at' => now(),
        ]);

        $response = $this->get(route('reports.show', $report));

        $response->assertOk();
        $response->assertSee('property="og:type" content="article"', false);
        $response->assertSee('property="article:published_time"', false);
        $response->assertSee('property="article:author" content="Lena Vogt"', false);
    }

    public function test_pinterest_tag_script_is_absent_when_not_configured(): void
    {
        $this->get(route('home'))->assertDontSee('s.pinimg.com', false);
    }

    public function test_pinterest_tag_script_is_present_when_configured(): void
    {
        Setting::set('pinterest_tag_id', '2612345678');

        $response = $this->get(route('home'));

        $response->assertSee('s.pinimg.com', false);
        $response->assertSee('hasMarketingConsent', false);
        $response->assertSee('2612345678', false);
    }

    public function test_admin_can_save_the_pinterest_tag_id(): void
    {
        $response = $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validSettingsPayload([
            'pinterest_tag_id' => '2612345678',
        ]));

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertSame('2612345678', Setting::get('pinterest_tag_id'));
    }
}

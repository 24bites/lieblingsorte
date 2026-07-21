<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\TravelTip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PreviewFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Administrator', 'email' => 'admin@lieblingsorte.test',
            'password' => Hash::make('admin123'), 'role' => 'admin',
        ]);
    }

    private function unpublishedRegion(): Region
    {
        return Region::create([
            'name' => 'Entwurfsregion', 'type' => 'Region', 'country' => 'Testland',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => false,
        ]);
    }

    public function test_unpublished_region_is_404_on_public_route(): void
    {
        $region = $this->unpublishedRegion();

        $this->get(route('regions.show', $region))->assertNotFound();
    }

    public function test_admin_can_preview_unpublished_region(): void
    {
        $region = $this->unpublishedRegion();

        $response = $this->actingAs($this->admin())->get(route('admin.regions.preview', $region));

        $response->assertOk();
        $response->assertSee('Vorschau-Modus');
        $response->assertSee($region->name);
    }

    public function test_guest_cannot_reach_the_preview_route(): void
    {
        $region = $this->unpublishedRegion();

        $response = $this->get(route('admin.regions.preview', $region));

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_preview_unpublished_travel_tip(): void
    {
        $region = $this->unpublishedRegion();
        $tip = $region->travelTips()->create([
            'title' => 'Geheimer Aussichtspunkt',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => false,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.tips.preview', $tip));

        $response->assertOk();
        $response->assertSee('Vorschau-Modus');
        $response->assertSee($tip->title);
    }

    public function test_unpublished_travel_tip_is_404_on_public_route(): void
    {
        $region = Region::create([
            'name' => 'Veröffentlichte Region', 'type' => 'Region', 'country' => 'Testland',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => true,
        ]);
        $tip = $region->travelTips()->create([
            'title' => 'Unveröffentlichter Tipp',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => false,
        ]);

        $this->get(route('tips.show', [$region, $tip->slug]))->assertNotFound();
    }
}

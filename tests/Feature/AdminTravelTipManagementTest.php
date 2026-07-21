<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTravelTipManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Administrator', 'email' => 'admin@lieblingsorte.test',
            'password' => Hash::make('admin123'), 'role' => 'admin',
        ]);
    }

    private function region(): Region
    {
        return Region::create([
            'name' => 'Südtirol', 'slug' => 'suedtirol', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'x', 'description' => 'y', 'is_published' => true,
        ]);
    }

    public function test_admin_can_create_a_travel_tip_with_automatically_generated_slug(): void
    {
        $region = $this->region();

        $response = $this->actingAs($this->admin())->post('/admin/reisetipps', [
            'region_id' => $region->id,
            'title' => 'Kalterer See',
            'short_description' => 'Kurzbeschreibung',
            'description' => 'Ausführliche Beschreibung',
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('admin.tips.index'));
        $this->assertDatabaseHas('travel_tips', ['title' => 'Kalterer See', 'slug' => 'kalterer-see', 'region_id' => $region->id]);
    }

    public function test_travel_tip_slug_is_only_unique_within_its_region(): void
    {
        $southTyrol = $this->region();
        $allgaeu = Region::create([
            'name' => 'Allgäu', 'slug' => 'allgaeu', 'type' => 'Reisegebiet', 'country' => 'Deutschland',
            'short_description' => 'x', 'description' => 'y', 'is_published' => true,
        ]);

        $southTyrol->travelTips()->create([
            'title' => 'Aussichtspunkt', 'slug' => 'aussichtspunkt',
            'short_description' => 'x', 'description' => 'y', 'is_published' => true,
        ]);

        $this->actingAs($this->admin())->post('/admin/reisetipps', [
            'region_id' => $allgaeu->id,
            'title' => 'Aussichtspunkt',
            'short_description' => 'Kurzbeschreibung',
            'description' => 'Beschreibung',
            'is_published' => '1',
        ]);

        // Same slug is fine because it belongs to a different region.
        $this->assertDatabaseHas('travel_tips', ['region_id' => $allgaeu->id, 'slug' => 'aussichtspunkt']);
        $this->assertDatabaseHas('travel_tips', ['region_id' => $southTyrol->id, 'slug' => 'aussichtspunkt']);
    }

    public function test_travel_tip_slug_collision_within_same_region_is_avoided(): void
    {
        $region = $this->region();
        $region->travelTips()->create([
            'title' => 'Kalterer See', 'slug' => 'kalterer-see',
            'short_description' => 'x', 'description' => 'y', 'is_published' => true,
        ]);

        $this->actingAs($this->admin())->post('/admin/reisetipps', [
            'region_id' => $region->id,
            'title' => 'Kalterer See',
            'short_description' => 'Zweiter Tipp mit gleichem Titel',
            'description' => 'Beschreibung',
            'is_published' => '1',
        ]);

        $this->assertDatabaseHas('travel_tips', ['region_id' => $region->id, 'slug' => 'kalterer-see-2']);
    }

    public function test_travel_tip_requires_a_valid_region(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/reisetipps', [
            'region_id' => 9999,
            'title' => 'Ungültiger Tipp',
            'short_description' => 'x',
            'description' => 'y',
        ]);

        $response->assertSessionHasErrors('region_id');
    }
}

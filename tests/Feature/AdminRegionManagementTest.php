<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminRegionManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Administrator', 'email' => 'admin@lieblingsorte.test',
            'password' => Hash::make('admin123'), 'role' => 'admin',
        ]);
    }

    public function test_admin_can_create_a_region_with_automatically_generated_slug(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/regionen', [
            'name' => 'Südtirol',
            'type' => 'Region',
            'country' => 'Italien',
            'short_description' => 'Kurzbeschreibung',
            'description' => 'Ausführliche Beschreibung',
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('admin.regions.index'));
        $this->assertDatabaseHas('regions', ['name' => 'Südtirol', 'slug' => 'suedtirol']);
    }

    public function test_region_slug_collisions_are_avoided(): void
    {
        Region::create([
            'name' => 'Südtirol', 'slug' => 'suedtirol', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'x', 'description' => 'y', 'is_published' => true,
        ]);

        $this->actingAs($this->admin())->post('/admin/regionen', [
            'name' => 'Südtirol',
            'type' => 'Region',
            'country' => 'Italien',
            'short_description' => 'Zweite Region mit gleichem Namen',
            'description' => 'Beschreibung',
            'is_published' => '1',
        ]);

        $this->assertDatabaseHas('regions', ['slug' => 'suedtirol-2']);
    }

    public function test_admin_can_edit_an_existing_region(): void
    {
        $region = Region::create([
            'name' => 'Südtirol', 'slug' => 'suedtirol', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Alt', 'description' => 'Alt', 'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin())->put("/admin/regionen/{$region->slug}", [
            'name' => 'Südtirol',
            'type' => 'Region',
            'country' => 'Italien',
            'short_description' => 'Neue Kurzbeschreibung',
            'description' => 'Neue Beschreibung',
            'is_published' => '1',
        ]);

        $response->assertRedirect(route('admin.regions.index'));
        $this->assertDatabaseHas('regions', ['id' => $region->id, 'short_description' => 'Neue Kurzbeschreibung']);
    }

    public function test_admin_can_delete_a_region(): void
    {
        $region = Region::create([
            'name' => 'Löschregion', 'slug' => 'loeschregion', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'x', 'description' => 'y', 'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin())->delete("/admin/regionen/{$region->slug}");

        $response->assertRedirect(route('admin.regions.index'));
        $this->assertDatabaseMissing('regions', ['id' => $region->id]);
    }

    public function test_region_requires_mandatory_fields(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/regionen', []);

        $response->assertSessionHasErrors(['name', 'type', 'country', 'short_description', 'description']);
    }
}

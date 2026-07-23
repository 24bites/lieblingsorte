<?php

namespace Tests\Feature;

use App\Models\PinterestFeedFeature;
use App\Models\Region;
use App\Models\TravelTip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PinterestFeedCurationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Administrator', 'email' => 'admin@lieblingsorte.test',
            'password' => Hash::make('admin123'), 'role' => 'admin',
        ]);
    }

    private function region(array $overrides = []): Region
    {
        return Region::create(array_merge([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'x', 'description' => 'y', 'is_published' => true,
        ], $overrides));
    }

    private function tip(array $overrides = []): TravelTip
    {
        return TravelTip::create(array_merge([
            'region_id' => $this->region(['name' => 'Region-'.uniqid()])->id,
            'title' => 'Piazza del Campo', 'short_description' => 'x', 'description' => 'y',
            'is_published' => true,
        ], $overrides));
    }

    public function test_index_lists_regions_by_default(): void
    {
        $this->region(['name' => 'Südtirol']);

        $response = $this->actingAs($this->admin())->get(route('admin.pinterest-feed-curation.index'));

        $response->assertOk()->assertSee('Südtirol');
    }

    public function test_index_can_filter_to_travel_tips(): void
    {
        $this->tip(['title' => 'Piazza del Campo']);

        $response = $this->actingAs($this->admin())->get(route('admin.pinterest-feed-curation.index', ['type' => 'tip']));

        $response->assertOk()->assertSee('Piazza del Campo');
    }

    public function test_search_filters_by_title(): void
    {
        $this->region(['name' => 'Südtirol']);
        $this->region(['name' => 'Allgäu']);

        $response = $this->actingAs($this->admin())->get(route('admin.pinterest-feed-curation.index', ['q' => 'Süd']));

        $response->assertOk()->assertSee('Südtirol')->assertDontSee('Allgäu');
    }

    public function test_admin_can_add_a_region_to_the_feed(): void
    {
        $region = $this->region();

        $response = $this->actingAs($this->admin())->post(route('admin.pinterest-feed-curation.store'), [
            'type' => 'region', 'id' => $region->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pinterest_feed_features', [
            'featurable_type' => Region::class, 'featurable_id' => $region->id,
        ]);
    }

    public function test_adding_the_same_item_twice_does_not_create_a_duplicate(): void
    {
        $region = $this->region();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.pinterest-feed-curation.store'), ['type' => 'region', 'id' => $region->id]);
        $this->actingAs($admin)->post(route('admin.pinterest-feed-curation.store'), ['type' => 'region', 'id' => $region->id]);

        $this->assertSame(1, PinterestFeedFeature::where('featurable_id', $region->id)->count());
    }

    public function test_admin_can_remove_an_item_from_the_feed(): void
    {
        $region = $this->region();
        $feature = PinterestFeedFeature::create(['featurable_type' => Region::class, 'featurable_id' => $region->id, 'sort_order' => 0]);

        $response = $this->actingAs($this->admin())->delete(route('admin.pinterest-feed-curation.destroy', $feature));

        $response->assertRedirect();
        $this->assertDatabaseMissing('pinterest_feed_features', ['id' => $feature->id]);
    }

    public function test_admin_can_reorder_curated_items(): void
    {
        $first = PinterestFeedFeature::create(['featurable_type' => Region::class, 'featurable_id' => $this->region()->id, 'sort_order' => 0]);
        $second = PinterestFeedFeature::create(['featurable_type' => Region::class, 'featurable_id' => $this->region()->id, 'sort_order' => 1]);

        $this->actingAs($this->admin())->patch(route('admin.pinterest-feed-curation.down', $first));

        $this->assertSame(1, $first->fresh()->sort_order);
        $this->assertSame(0, $second->fresh()->sort_order);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.pinterest-feed-curation.index'))->assertRedirect(route('admin.login'));
    }
}

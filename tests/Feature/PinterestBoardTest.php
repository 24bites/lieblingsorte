<?php

namespace Tests\Feature;

use App\Models\PinterestBoard;
use App\Models\PinterestPin;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PinterestBoardTest extends TestCase
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

    public function test_index_lists_boards_and_regions_without_a_board(): void
    {
        $this->region(['name' => 'Südtirol']);
        PinterestBoard::create(['type' => 'topic', 'name' => 'Geheimtipps Europa']);

        $response = $this->actingAs($this->admin())->get(route('admin.pinterest-boards.index'));

        $response->assertOk()->assertSee('Geheimtipps Europa')->assertSee('Südtirol');
    }

    public function test_admin_can_create_a_topic_board(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.pinterest-boards.store'), [
            'type' => 'topic', 'name' => 'Badeseen Alpen', 'description' => 'Seen zum Baden',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pinterest_boards', [
            'type' => 'topic', 'name' => 'Badeseen Alpen', 'description' => 'Seen zum Baden',
        ]);
    }

    public function test_admin_can_create_a_region_board(): void
    {
        $region = $this->region();

        $response = $this->actingAs($this->admin())->post(route('admin.pinterest-boards.store'), [
            'type' => 'region', 'name' => $region->name, 'region_id' => $region->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pinterest_boards', [
            'type' => 'region', 'region_id' => $region->id,
        ]);
    }

    public function test_region_board_without_region_id_fails_validation(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.pinterest-boards.store'), [
            'type' => 'region', 'name' => 'Ohne Region',
        ]);

        $response->assertSessionHasErrors('region_id');
        $this->assertDatabaseMissing('pinterest_boards', ['name' => 'Ohne Region']);
    }

    public function test_admin_can_update_a_board(): void
    {
        $board = PinterestBoard::create(['type' => 'topic', 'name' => 'Alt']);

        $response = $this->actingAs($this->admin())->put(route('admin.pinterest-boards.update', $board), [
            'name' => 'Neu', 'description' => 'Neue Beschreibung',
        ]);

        $response->assertRedirect();
        $this->assertSame('Neu', $board->fresh()->name);
        $this->assertSame('Neue Beschreibung', $board->fresh()->description);
    }

    public function test_admin_can_delete_a_board_without_pins(): void
    {
        $board = PinterestBoard::create(['type' => 'topic', 'name' => 'Löschbar']);

        $response = $this->actingAs($this->admin())->delete(route('admin.pinterest-boards.destroy', $board));

        $response->assertRedirect();
        $this->assertDatabaseMissing('pinterest_boards', ['id' => $board->id]);
    }

    public function test_deleting_a_board_with_pins_is_blocked(): void
    {
        $region = $this->region();
        $board = PinterestBoard::create(['type' => 'topic', 'name' => 'In Benutzung']);
        PinterestPin::create([
            'featurable_type' => Region::class, 'featurable_id' => $region->id,
            'board_id' => $board->id, 'variant_label' => 'allgemein',
        ]);

        $response = $this->actingAs($this->admin())->delete(route('admin.pinterest-boards.destroy', $board));

        $response->assertSessionHasErrors('board');
        $this->assertDatabaseHas('pinterest_boards', ['id' => $board->id]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.pinterest-boards.index'))->assertRedirect(route('admin.login'));
    }
}

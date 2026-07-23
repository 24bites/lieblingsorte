<?php

namespace Tests\Feature;

use App\Models\PinterestBoard;
use App\Models\PinterestPin;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PinterestPinControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

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

    private function fakeCoverPhoto(Region $region): void
    {
        $image = imagecreatetruecolor(1600, 900);
        imagefill($image, 0, 0, imagecolorallocate($image, 90, 130, 110));
        ob_start();
        imagejpeg($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        $path = "regions/{$region->id}/cover.jpg";
        Storage::disk('public')->put($path, $contents);

        $region->media()->create(['file_path' => $path, 'is_cover' => true, 'sort_order' => 0]);
    }

    private function fakeOpenAi(): void
    {
        config(['services.openai.key' => 'test-key']);
        Http::fake(['api.openai.com/v1/chat/completions' => Http::response([
            'model' => 'gpt-4o-mini',
            'choices' => [['message' => ['content' => json_encode([
                'overlay_headline' => 'Kalterer See',
                'overlay_subline' => 'Geheimtipp',
                'pin_title' => 'Kalterer See Suedtirol: Bester Badesee',
                'pin_description' => str_repeat('Ein schoener Badesee in Suedtirol. ', 10).'#suedtirol #kalterersee',
            ])]]],
            'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 80, 'total_tokens' => 180],
        ])]);
    }

    public function test_admin_can_create_a_pin_for_multiple_boards_at_once(): void
    {
        $this->fakeOpenAi();
        $region = $this->region();
        $this->fakeCoverPhoto($region);
        $boardA = PinterestBoard::create(['type' => 'region', 'name' => 'Toskana', 'region_id' => $region->id]);
        $boardB = PinterestBoard::create(['type' => 'topic', 'name' => 'Geheimtipps Europa']);

        $response = $this->actingAs($this->admin())->post(route('admin.pinterest-pins.store'), [
            'type' => 'region', 'id' => $region->id, 'angle' => 'geheimtipp',
            'board_ids' => [$boardA->id, $boardB->id],
        ]);

        $response->assertRedirect(route('admin.pinterest-pins.index'));
        $this->assertSame(2, PinterestPin::count());
        $this->assertSame(2, PinterestPin::where('status', 'draft')->count());
        $this->assertSame('Kalterer See', PinterestPin::first()->overlay_headline);

        $pin = PinterestPin::first();
        $this->assertTrue(Storage::disk('public')->exists($pin->generated_image_path));
    }

    public function test_store_fails_gracefully_without_a_cover_image(): void
    {
        $this->fakeOpenAi();
        $region = $this->region();
        $board = PinterestBoard::create(['type' => 'topic', 'name' => 'Geheimtipps Europa']);

        $response = $this->actingAs($this->admin())->post(route('admin.pinterest-pins.store'), [
            'type' => 'region', 'id' => $region->id, 'angle' => 'allgemein', 'board_ids' => [$board->id],
        ]);

        $response->assertSessionHasErrors('generate');
        $this->assertSame(0, PinterestPin::count());
    }

    public function test_admin_can_approve_a_draft_pin(): void
    {
        $region = $this->region();
        $board = PinterestBoard::create(['type' => 'topic', 'name' => 'Geheimtipps Europa']);
        $pin = PinterestPin::create([
            'featurable_type' => Region::class, 'featurable_id' => $region->id,
            'board_id' => $board->id, 'variant_label' => 'allgemein', 'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.pinterest-pins.approve', $pin));

        $response->assertRedirect();
        $this->assertSame('approved', $pin->fresh()->status);
    }

    public function test_approving_a_non_draft_pin_is_blocked(): void
    {
        $region = $this->region();
        $board = PinterestBoard::create(['type' => 'topic', 'name' => 'Geheimtipps Europa']);
        $pin = PinterestPin::create([
            'featurable_type' => Region::class, 'featurable_id' => $region->id,
            'board_id' => $board->id, 'variant_label' => 'allgemein', 'status' => 'approved',
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.pinterest-pins.approve', $pin));

        $response->assertSessionHasErrors('approve');
    }

    public function test_publish_is_blocked_while_pinterest_is_not_connected(): void
    {
        $region = $this->region();
        $board = PinterestBoard::create(['type' => 'topic', 'name' => 'Geheimtipps Europa']);
        $pin = PinterestPin::create([
            'featurable_type' => Region::class, 'featurable_id' => $region->id,
            'board_id' => $board->id, 'variant_label' => 'allgemein', 'status' => 'approved',
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.pinterest-pins.publish', $pin));

        $response->assertSessionHasErrors('publish');
        $this->assertSame('approved', $pin->fresh()->status);
    }

    public function test_admin_can_update_pin_title_and_description(): void
    {
        $region = $this->region();
        $board = PinterestBoard::create(['type' => 'topic', 'name' => 'Geheimtipps Europa']);
        $pin = PinterestPin::create([
            'featurable_type' => Region::class, 'featurable_id' => $region->id,
            'board_id' => $board->id, 'variant_label' => 'allgemein', 'status' => 'draft',
            'pin_title' => 'Alt', 'pin_description' => 'Alt',
        ]);

        $response = $this->actingAs($this->admin())->put(route('admin.pinterest-pins.update', $pin), [
            'pin_title' => 'Neuer Titel', 'pin_description' => 'Neue Beschreibung',
        ]);

        $response->assertRedirect();
        $this->assertSame('Neuer Titel', $pin->fresh()->pin_title);
    }

    public function test_admin_can_delete_a_pin(): void
    {
        $region = $this->region();
        $board = PinterestBoard::create(['type' => 'topic', 'name' => 'Geheimtipps Europa']);
        $pin = PinterestPin::create([
            'featurable_type' => Region::class, 'featurable_id' => $region->id,
            'board_id' => $board->id, 'variant_label' => 'allgemein', 'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin())->delete(route('admin.pinterest-pins.destroy', $pin));

        $response->assertRedirect(route('admin.pinterest-pins.index'));
        $this->assertDatabaseMissing('pinterest_pins', ['id' => $pin->id]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.pinterest-pins.index'))->assertRedirect(route('admin.login'));
    }
}

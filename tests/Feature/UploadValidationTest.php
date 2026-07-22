<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadValidationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Administrator', 'email' => 'admin@lieblingsorte.test',
            'password' => Hash::make('admin123'), 'role' => 'admin',
        ]);
    }

    public function test_hero_image_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('not-an-image.txt', 10, 'text/plain');

        $response = $this->actingAs($this->admin())->post('/admin/regionen', [
            'name' => 'Südtirol',
            'type' => 'Region',
            'country' => 'Italien',
            'short_description' => 'x',
            'description' => 'y',
            'is_published' => '1',
            'hero_image' => $file,
        ]);

        $response->assertSessionHasErrors('hero_image');
        $this->assertDatabaseMissing('regions', ['name' => 'Südtirol']);
    }

    public function test_hero_image_upload_rejects_oversized_files(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('too-big.jpg')->size(30000);

        $response = $this->actingAs($this->admin())->post('/admin/regionen', [
            'name' => 'Südtirol',
            'type' => 'Region',
            'country' => 'Italien',
            'short_description' => 'x',
            'description' => 'y',
            'is_published' => '1',
            'hero_image' => $file,
        ]);

        $response->assertSessionHasErrors('hero_image');
    }

    public function test_hero_image_upload_rejects_images_that_are_too_small(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('tiny.jpg', 50, 50);

        $response = $this->actingAs($this->admin())->post('/admin/regionen', [
            'name' => 'Südtirol',
            'type' => 'Region',
            'country' => 'Italien',
            'short_description' => 'x',
            'description' => 'y',
            'is_published' => '1',
            'hero_image' => $file,
        ]);

        $response->assertSessionHasErrors('hero_image');
    }

    public function test_valid_hero_image_is_accepted_and_stored(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('suedtirol.jpg', 1600, 900)->size(500);

        $response = $this->actingAs($this->admin())->post('/admin/regionen', [
            'name' => 'Südtirol',
            'type' => 'Region',
            'country' => 'Italien',
            'short_description' => 'x',
            'description' => 'y',
            'is_published' => '1',
            'hero_image' => $file,
        ]);

        $response->assertRedirect(route('admin.regions.index'));
        $region = Region::where('name', 'Südtirol')->firstOrFail();
        $this->assertTrue($region->media()->where('is_cover', true)->exists());
    }

    public function test_large_iphone_sized_photo_is_now_accepted(): void
    {
        Storage::fake('public');

        // Between the old 5 MB Laravel cap and the new 25 MB one - exactly
        // the range that made real iPhone photos fail before this fix.
        $file = UploadedFile::fake()->image('iphone-foto.jpg', 4032, 3024)->size(15000);

        $response = $this->actingAs($this->admin())->post('/admin/regionen', [
            'name' => 'Südtirol',
            'type' => 'Region',
            'country' => 'Italien',
            'short_description' => 'x',
            'description' => 'y',
            'is_published' => '1',
            'hero_image' => $file,
        ]);

        $response->assertSessionDoesntHaveErrors('hero_image');
        $response->assertRedirect(route('admin.regions.index'));
    }

    public function test_uploaded_cover_image_gets_a_downscaled_web_optimized_variant(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('suedtirol.jpg', 4032, 3024)->size(500);

        $this->actingAs($this->admin())->post('/admin/regionen', [
            'name' => 'Südtirol',
            'type' => 'Region',
            'country' => 'Italien',
            'short_description' => 'x',
            'description' => 'y',
            'is_published' => '1',
            'hero_image' => $file,
        ]);

        $region = Region::where('name', 'Südtirol')->firstOrFail();
        $cover = $region->media()->where('is_cover', true)->firstOrFail();

        $this->assertNotNull($cover->optimized_path);
        Storage::disk('public')->assertExists($cover->optimized_path);

        [$width, $height] = getimagesize(Storage::disk('public')->path($cover->optimized_path));
        $this->assertLessThanOrEqual(2000, max($width, $height));
    }
}

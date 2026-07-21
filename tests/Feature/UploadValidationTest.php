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

        $file = UploadedFile::fake()->image('too-big.jpg')->size(6000);

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
}

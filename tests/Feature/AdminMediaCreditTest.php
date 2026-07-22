<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminMediaCreditTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Administrator', 'email' => 'admin@lieblingsorte.test',
            'password' => Hash::make('admin123'), 'role' => 'admin',
        ]);
    }

    private function media()
    {
        $region = Region::create([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => true,
        ]);

        return $region->media()->create([
            'file_path' => 'regions/toskana/toskana-1.jpg', 'alt_text' => 'Toskana',
            'sort_order' => 0, 'is_cover' => true, 'source' => 'wikimedia',
        ]);
    }

    public function test_admin_can_update_media_credit_fields(): void
    {
        $media = $this->media();

        $response = $this->actingAs($this->admin())->patch(route('admin.media.credit', $media), [
            'credit_author' => 'Max Mustermann',
            'credit_license' => 'CC BY-SA 4.0',
            'credit_source_title' => 'File:Toskana.jpg',
            'credit_source_url' => 'https://commons.wikimedia.org/wiki/File:Toskana.jpg',
        ]);

        $response->assertRedirect();
        $media->refresh();
        $this->assertSame('Max Mustermann', $media->credit_author);
        $this->assertSame('CC BY-SA 4.0', $media->credit_license);
        $this->assertSame('https://commons.wikimedia.org/wiki/File:Toskana.jpg', $media->credit_source_url);
    }

    public function test_credit_fields_can_be_cleared(): void
    {
        $media = $this->media();
        $media->update(['credit_author' => 'Alter Autor']);

        $response = $this->actingAs($this->admin())->patch(route('admin.media.credit', $media), [
            'credit_author' => '',
        ]);

        $response->assertRedirect();
        $this->assertNull($media->fresh()->credit_author);
    }

    public function test_invalid_source_url_is_rejected(): void
    {
        $media = $this->media();

        $response = $this->actingAs($this->admin())->patch(route('admin.media.credit', $media), [
            'credit_source_url' => 'not-a-url',
        ]);

        $response->assertSessionHasErrors('credit_source_url');
        $this->assertNull($media->fresh()->credit_source_url);
    }

    public function test_guest_cannot_update_media_credit(): void
    {
        $media = $this->media();

        $response = $this->patch(route('admin.media.credit', $media), [
            'credit_author' => 'Sollte nicht gespeichert werden',
        ]);

        $response->assertRedirect(route('admin.login'));
        $this->assertNull($media->fresh()->credit_author);
    }

    public function test_media_index_shows_quelle_fehlt_for_media_without_credit(): void
    {
        $this->media();

        $response = $this->actingAs($this->admin())->get(route('admin.media.index'));

        $response->assertOk();
        $response->assertSee('Quelle fehlt');
    }
}

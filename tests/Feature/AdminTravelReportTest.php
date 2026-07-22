<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\TravelReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTravelReportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Administrator', 'email' => 'admin@lieblingsorte.test',
            'password' => Hash::make('admin123'), 'role' => 'admin',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Ein Wochenende auf Föhr',
            'excerpt' => 'Ein ruhiges Winterwochenende auf der Insel.',
            'content' => "Ein Absatz.\n\n## Zwischenüberschrift\n\nEin weiterer Absatz.",
            'author_name' => 'Anna',
            'is_published' => '1',
        ], $overrides);
    }

    public function test_admin_can_create_a_travel_report(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.reports.store'), $this->validPayload());

        $report = TravelReport::first();
        $this->assertNotNull($report);
        $response->assertRedirect(route('admin.reports.edit', $report));
        $this->assertSame('Ein Wochenende auf Föhr', $report->title);
        $this->assertTrue($report->is_published);
        $this->assertNotNull($report->published_at);
    }

    public function test_creating_unpublished_report_does_not_set_published_at(): void
    {
        $this->actingAs($this->admin())->post(route('admin.reports.store'), $this->validPayload(['is_published' => '0']));

        $report = TravelReport::first();
        $this->assertFalse($report->is_published);
        $this->assertNull($report->published_at);
    }

    public function test_publishing_later_sets_published_at_once(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.reports.store'), $this->validPayload(['is_published' => '0']));
        $report = TravelReport::first();

        $this->actingAs($admin)->put(route('admin.reports.update', $report), $this->validPayload(['is_published' => '1']));
        $report->refresh();
        $firstPublishedAt = $report->published_at;
        $this->assertNotNull($firstPublishedAt);

        $this->travel(1)->hours();
        $this->actingAs($admin)->put(route('admin.reports.update', $report), $this->validPayload(['is_published' => '1', 'title' => 'Geänderter Titel']));
        $report->refresh();

        $this->assertTrue($report->published_at->equalTo($firstPublishedAt));
    }

    public function test_validation_requires_core_fields(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.reports.store'), []);

        $response->assertSessionHasErrors(['title', 'excerpt', 'content', 'author_name']);
        $this->assertSame(0, TravelReport::count());
    }

    public function test_report_can_be_linked_to_a_region(): void
    {
        $region = Region::create([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => true,
        ]);

        $this->actingAs($this->admin())->post(route('admin.reports.store'), $this->validPayload(['region_id' => $region->id]));

        $this->assertSame($region->id, TravelReport::first()->region_id);
    }

    public function test_admin_can_upload_cover_and_gallery_images(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin())->post(route('admin.reports.store'), array_merge($this->validPayload(), [
            'cover_image' => UploadedFile::fake()->image('titel.jpg', 800, 600),
            'gallery_images' => [UploadedFile::fake()->image('galerie-1.jpg', 800, 600)],
        ]));

        $report = TravelReport::first();
        $this->assertSame(2, $report->media()->count());
        $this->assertTrue($report->media()->where('is_cover', true)->exists());
    }

    public function test_admin_can_delete_a_report(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.reports.store'), $this->validPayload());
        $report = TravelReport::first();

        $response = $this->actingAs($admin)->delete(route('admin.reports.destroy', $report));

        $response->assertRedirect(route('admin.reports.index'));
        $this->assertSame(0, TravelReport::count());
    }

    public function test_admin_can_preview_an_unpublished_report(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.reports.store'), $this->validPayload(['is_published' => '0']));
        $report = TravelReport::first();

        $response = $this->actingAs($admin)->get(route('admin.reports.preview', $report));

        $response->assertOk();
        $response->assertSee('Vorschau-Modus');
    }

    public function test_guest_cannot_reach_the_admin_report_routes(): void
    {
        $report = TravelReport::create([
            'title' => 'Ein Wochenende auf Föhr',
            'excerpt' => 'Kurz',
            'content' => 'Ein Absatz.',
            'author_name' => 'Anna',
        ]);

        $this->get(route('admin.reports.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.reports.edit', $report))->assertRedirect(route('admin.login'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AiSuggestionQueueTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Administrator', 'email' => 'admin@lieblingsorte.test',
            'password' => Hash::make('admin123'), 'role' => 'admin',
        ]);
    }

    private function aiRegion(string $name = 'Gardasee'): Region
    {
        return Region::create([
            'name' => $name, 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Kurz', 'description' => 'Lang',
            'is_published' => false, 'ai_generated' => true,
        ]);
    }

    public function test_queue_only_lists_pending_ai_generated_regions(): void
    {
        $pending = $this->aiRegion('Gardasee');
        $manualDraft = Region::create([
            'name' => 'Manuell', 'type' => 'Region', 'country' => 'Deutschland',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => false,
        ]);
        $alreadyPublished = $this->aiRegion('Bereits veröffentlicht');
        $alreadyPublished->update(['is_published' => true]);
        $alreadyRejected = $this->aiRegion('Bereits abgelehnt');
        $alreadyRejected->update(['rejected_at' => now()]);

        $response = $this->actingAs($this->admin())->get(route('admin.ai-suggestions.index'));

        $response->assertOk();
        $response->assertSee('Gardasee');
        $response->assertDontSee('Manuell');
        $response->assertDontSee('Bereits veröffentlicht');
        $response->assertDontSee('Bereits abgelehnt');
    }

    public function test_approve_publishes_the_region(): void
    {
        $region = $this->aiRegion();

        $response = $this->actingAs($this->admin())->post(route('admin.ai-suggestions.approve', $region));

        $response->assertRedirect();
        $this->assertTrue($region->fresh()->is_published);
    }

    public function test_reject_hides_it_from_the_queue_without_deleting_it(): void
    {
        $admin = $this->admin();
        $region = $this->aiRegion();

        $response = $this->actingAs($admin)->post(route('admin.ai-suggestions.reject', $region));

        $response->assertRedirect();
        $region->refresh();
        $this->assertNotNull($region->rejected_at);
        $this->assertFalse($region->is_published);
        $this->assertDatabaseHas('regions', ['id' => $region->id]);

        // First GET consumes the "... wurde abgelehnt" flash message, which
        // itself contains the region name; the second reflects the real page.
        $this->actingAs($admin)->get(route('admin.ai-suggestions.index'));
        $queue = $this->actingAs($admin)->get(route('admin.ai-suggestions.index'));
        $queue->assertDontSee($region->name);
    }
}

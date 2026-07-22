<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\SocialPost;
use App\Models\TravelReport;
use App\Models\TravelTip;
use App\Models\User;
use App\Support\TelegramConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminSocialHubTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Administrator', 'email' => 'admin@lieblingsorte.test',
            'password' => Hash::make('admin123'), 'role' => 'admin',
        ]);
    }

    private function fakeApiKey(): void
    {
        config(['services.openai.key' => 'test-key']);
    }

    private function fakeCaptionResponse(string $text = 'Schöne Toskana, jetzt entdecken.'): void
    {
        Http::fake(['api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => $text]]],
        ])]);
    }

    private function publishedRegion(): Region
    {
        return Region::create([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => true,
        ]);
    }

    private function publishedTip(): TravelTip
    {
        $region = $this->publishedRegion();

        return TravelTip::create([
            'region_id' => $region->id, 'title' => 'Piazza del Campo',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => true,
        ]);
    }

    private function publishedReport(): TravelReport
    {
        return TravelReport::create([
            'title' => 'Ein Wochenende in der Toskana', 'excerpt' => 'Kurz',
            'content' => 'Ein Absatz.', 'author_name' => 'Anna', 'is_published' => true,
        ]);
    }

    public function test_index_lists_published_regions_by_default(): void
    {
        $region = $this->publishedRegion();

        $response = $this->actingAs($this->admin())->get(route('admin.social-hub.index'));

        $response->assertOk();
        $response->assertSee($region->name);
    }

    public function test_index_can_filter_by_type(): void
    {
        $tip = $this->publishedTip();

        $response = $this->actingAs($this->admin())->get(route('admin.social-hub.index', ['type' => 'tip']));

        $response->assertOk();
        $response->assertSee($tip->title);
    }

    public function test_index_does_not_list_unpublished_content(): void
    {
        Region::create([
            'name' => 'Entwurf', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => false,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.social-hub.index'));

        $response->assertOk();
        $response->assertDontSee('Entwurf');
    }

    public function test_admin_can_generate_a_caption_for_a_region(): void
    {
        $this->fakeApiKey();
        $this->fakeCaptionResponse();
        $region = $this->publishedRegion();

        $response = $this->actingAs($this->admin())->post(route('admin.social-hub.generate'), [
            'type' => 'region', 'id' => $region->id, 'platform' => 'facebook',
        ]);

        $socialPost = SocialPost::first();
        $this->assertNotNull($socialPost);
        $response->assertRedirect(route('admin.social-hub.show', $socialPost));
        $this->assertSame('facebook', $socialPost->platform);
        $this->assertSame('draft', $socialPost->status);
        $this->assertNotEmpty($socialPost->caption);
        $this->assertSame(route('regions.show', $region), $socialPost->link_url);
    }

    public function test_generating_again_overwrites_the_existing_draft_for_that_platform(): void
    {
        $this->fakeApiKey();
        $region = $this->publishedRegion();
        $admin = $this->admin();

        Http::fake(['api.openai.com/v1/chat/completions' => Http::sequence()
            ->push(['choices' => [['message' => ['content' => 'Erster Text']]]])
            ->push(['choices' => [['message' => ['content' => 'Zweiter Text']]]])]);

        $this->actingAs($admin)->post(route('admin.social-hub.generate'), [
            'type' => 'region', 'id' => $region->id, 'platform' => 'facebook',
        ]);
        $this->actingAs($admin)->post(route('admin.social-hub.generate'), [
            'type' => 'region', 'id' => $region->id, 'platform' => 'facebook',
        ]);

        $this->assertSame(1, SocialPost::count());
        $this->assertSame('Zweiter Text', SocialPost::first()->caption);
    }

    public function test_generate_failure_shows_error_without_creating_a_post(): void
    {
        $this->fakeApiKey();
        Http::fake(['api.openai.com/v1/chat/completions' => Http::response(['error' => ['message' => 'boom']], 500)]);
        $region = $this->publishedRegion();

        $response = $this->actingAs($this->admin())->post(route('admin.social-hub.generate'), [
            'type' => 'region', 'id' => $region->id, 'platform' => 'facebook',
        ]);

        $response->assertSessionHasErrors('generate');
        $this->assertSame(0, SocialPost::count());
    }

    public function test_admin_can_update_the_caption(): void
    {
        $region = $this->publishedRegion();
        $socialPost = $region->socialPosts()->create([
            'platform' => 'facebook', 'caption' => 'Alt', 'link_url' => 'https://example.test', 'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin())->put(route('admin.social-hub.update', $socialPost), [
            'caption' => 'Neuer Text',
        ]);

        $response->assertRedirect();
        $this->assertSame('Neuer Text', $socialPost->fresh()->caption);
    }

    public function test_show_page_renders_the_share_link_when_telegram_not_configured(): void
    {
        $region = $this->publishedRegion();
        $socialPost = $region->socialPosts()->create([
            'platform' => 'facebook', 'caption' => 'Text', 'link_url' => 'https://example.test', 'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.social-hub.show', $socialPost));

        $response->assertOk();
        $response->assertSee('facebook.com/sharer', false);
        $response->assertDontSee('Jetzt an Telegram senden');
    }

    public function test_admin_can_mark_a_post_as_sent(): void
    {
        $region = $this->publishedRegion();
        $socialPost = $region->socialPosts()->create([
            'platform' => 'facebook', 'caption' => 'Text', 'link_url' => 'https://example.test', 'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.social-hub.mark-sent', $socialPost));

        $response->assertRedirect();
        $socialPost->refresh();
        $this->assertSame('sent', $socialPost->status);
        $this->assertNotNull($socialPost->sent_at);
    }

    public function test_admin_can_send_a_telegram_post_when_configured(): void
    {
        TelegramConfig::store('123456:ABC-token', '@meinkanal');
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $report = $this->publishedReport();
        $socialPost = $report->socialPosts()->create([
            'platform' => 'telegram', 'caption' => 'Text', 'link_url' => 'https://example.test', 'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.social-hub.send', $socialPost));

        $response->assertRedirect();
        $socialPost->refresh();
        $this->assertSame('sent', $socialPost->status);
    }

    public function test_sending_a_non_telegram_post_is_not_allowed(): void
    {
        $region = $this->publishedRegion();
        $socialPost = $region->socialPosts()->create([
            'platform' => 'facebook', 'caption' => 'Text', 'link_url' => 'https://example.test', 'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.social-hub.send', $socialPost));

        $response->assertNotFound();
    }

    public function test_telegram_send_failure_marks_post_as_failed(): void
    {
        TelegramConfig::store('123456:ABC-token', '@meinkanal');
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'chat not found'], 400)]);
        $region = $this->publishedRegion();
        $socialPost = $region->socialPosts()->create([
            'platform' => 'telegram', 'caption' => 'Text', 'link_url' => 'https://example.test', 'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.social-hub.send', $socialPost));

        $response->assertSessionHasErrors('send');
        $this->assertSame('failed', $socialPost->fresh()->status);
    }

    public function test_admin_can_delete_a_draft(): void
    {
        $region = $this->publishedRegion();
        $socialPost = $region->socialPosts()->create([
            'platform' => 'facebook', 'caption' => 'Text', 'link_url' => 'https://example.test', 'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin())->delete(route('admin.social-hub.destroy', $socialPost));

        $response->assertRedirect(route('admin.social-hub.index'));
        $this->assertSame(0, SocialPost::count());
    }

    public function test_guest_cannot_reach_social_hub_routes(): void
    {
        $region = $this->publishedRegion();
        $socialPost = $region->socialPosts()->create([
            'platform' => 'facebook', 'caption' => 'Text', 'link_url' => 'https://example.test', 'status' => 'draft',
        ]);

        $this->get(route('admin.social-hub.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.social-hub.show', $socialPost))->assertRedirect(route('admin.login'));
    }
}

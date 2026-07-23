<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\TravelTip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiFeatureTest extends TestCase
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

    public function test_ai_image_generation_is_hidden_when_no_api_key_configured(): void
    {
        config(['services.openai.key' => null]);
        $region = Region::create([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.regions.edit', $region));

        $response->assertOk();
        $response->assertSee('OpenAI-API-Key');
        $response->assertDontSee('Mit KI generieren');
    }

    public function test_admin_can_generate_an_ai_image_for_a_region(): void
    {
        Storage::fake('public');
        $this->fakeApiKey();
        $fakeImage = base64_encode('fake-png-bytes');
        Http::fake([
            'api.openai.com/v1/images/generations' => Http::response([
                'data' => [['b64_json' => $fakeImage]],
            ], 200),
        ]);

        $region = Region::create([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.regions.ai-image', $region), [
            'ai_prompt' => 'Landschaftsfoto der Toskana, professionelle Reisefotografie',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('media', 1);
        $region->refresh();
        $this->assertNotNull($region->hero_image);
        $this->assertTrue($region->media()->first()->is_cover);
        $this->assertDatabaseHas('ai_usage_logs', ['feature' => 'image', 'model' => 'gpt-image-1']);
    }

    public function test_ai_image_generation_failure_returns_validation_error_without_creating_media(): void
    {
        $this->fakeApiKey();
        Http::fake([
            'api.openai.com/v1/images/generations' => Http::response(['error' => ['message' => 'rate limited']], 429),
        ]);

        $region = Region::create([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.regions.ai-image', $region), [
            'ai_prompt' => 'Landschaftsfoto der Toskana',
        ]);

        $response->assertSessionHasErrors('ai_prompt');
        $this->assertDatabaseCount('media', 0);
    }

    public function test_admin_can_generate_an_ai_image_for_a_travel_tip(): void
    {
        Storage::fake('public');
        $this->fakeApiKey();
        $fakeImage = base64_encode('fake-png-bytes');
        Http::fake([
            'api.openai.com/v1/images/generations' => Http::response([
                'data' => [['b64_json' => $fakeImage]],
            ], 200),
        ]);

        $region = Region::create([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => true,
        ]);
        $tip = TravelTip::create([
            'region_id' => $region->id, 'title' => 'Piazza del Campo',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => true,
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.tips.ai-image', $tip), [
            'ai_prompt' => 'Foto der Piazza del Campo in Siena',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('media', 1);
    }

    public function test_ai_region_generator_creates_an_unpublished_draft_region_with_tips(): void
    {
        $this->fakeApiKey();
        $draft = [
            'name' => 'Salzburg',
            'type' => 'Stadt',
            'country' => 'Österreich',
            'federal_state' => 'Salzburg',
            'best_travel_time' => 'Frühling bis Herbst',
            'short_description' => 'Mozarts Geburtsstadt am Rand der Alpen.',
            'description' => 'Salzburg besticht durch seine barocke Altstadt und die Bergkulisse.',
            'arrival_information' => 'Erreichbar per Bahn oder Flugzeug.',
            'latitude' => 47.8095,
            'longitude' => 13.0550,
            'seo_title' => null,
            'seo_description' => null,
            'tips' => [
                [
                    'title' => 'Festung Hohensalzburg',
                    'short_description' => 'Wahrzeichen der Stadt.',
                    'description' => 'Eine der größten erhaltenen Burgen Europas mit Blick über die Stadt.',
                    'highlights' => ['Bergbahn', 'Aussicht'],
                    'location_name' => 'Altstadt',
                    'address' => null,
                    'latitude' => 47.7952,
                    'longitude' => 13.0478,
                    'duration' => '2 Stunden',
                    'difficulty' => 'leicht',
                    'best_season' => 'ganzjährig',
                    'price_information' => 'Eintritt kostenpflichtig',
                    'opening_hours' => null,
                    'parking_information' => null,
                    'arrival_information' => null,
                    'website_url' => null,
                    'phone' => null,
                    'email' => null,
                    'rating' => 4.7,
                    'family_friendly' => true,
                    'stroller_friendly' => false,
                    'dog_friendly' => false,
                    'indoor' => false,
                    'free_entry' => false,
                    'featured' => true,
                ],
                [
                    'title' => 'Mirabellgarten',
                    'short_description' => 'Barocker Garten mit Blick auf die Festung.',
                    'description' => 'Weitläufige Gartenanlage, bekannt aus "The Sound of Music".',
                    'highlights' => [],
                    'family_friendly' => true,
                    'free_entry' => true,
                ],
            ],
        ];

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode($draft)]],
                ],
                'usage' => ['prompt_tokens' => 500, 'completion_tokens' => 800, 'total_tokens' => 1300],
            ], 200),
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.ai-region-generator.store'), [
            'place_name' => 'Salzburg',
            'tip_count' => 15,
        ]);

        $region = Region::where('name', 'Salzburg')->first();
        $this->assertNotNull($region);
        $response->assertRedirect(route('admin.regions.edit', $region));
        $this->assertFalse($region->is_published);
        $this->assertSame(2, $region->travelTips()->count());
        $this->assertTrue($region->travelTips()->where('is_published', false)->exists());
        $this->assertDatabaseHas('travel_tips', ['title' => 'Festung Hohensalzburg', 'region_id' => $region->id]);
        $this->assertDatabaseHas('ai_usage_logs', ['feature' => 'region_draft', 'total_tokens' => 1300]);
    }

    public function test_ai_region_generator_shows_error_when_openai_request_fails(): void
    {
        $this->fakeApiKey();
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response(['error' => ['message' => 'server error']], 500),
        ]);

        $response = $this->actingAs($this->admin())->post(route('admin.ai-region-generator.store'), [
            'place_name' => 'Salzburg',
        ]);

        $response->assertSessionHasErrors('place_name');
        $this->assertDatabaseCount('regions', 0);
    }
}

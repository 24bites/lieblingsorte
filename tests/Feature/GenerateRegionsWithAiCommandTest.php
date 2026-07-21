<?php

namespace Tests\Feature;

use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GenerateRegionsWithAiCommandTest extends TestCase
{
    use RefreshDatabase;

    private function fakeApiKey(): void
    {
        config(['services.openai.key' => 'test-key']);
    }

    private function suggestionResponse(string $name): array
    {
        return [
            'choices' => [
                ['message' => ['content' => json_encode(['name' => $name])]],
            ],
        ];
    }

    private function draftResponse(string $name, int $tipCount = 1): array
    {
        $draft = [
            'name' => $name,
            'type' => 'Region',
            'country' => 'Testland',
            'short_description' => 'Kurz',
            'description' => 'Lang',
            'tips' => [],
        ];

        for ($i = 1; $i <= $tipCount; $i++) {
            $draft['tips'][] = ['title' => "Tipp {$i}", 'short_description' => 'Kurz'];
        }

        return [
            'choices' => [
                ['message' => ['content' => json_encode($draft)]],
            ],
        ];
    }

    public function test_generates_regions_up_to_limit_and_marks_them_ai_generated(): void
    {
        $this->fakeApiKey();
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::sequence()
                ->push($this->suggestionResponse('Gardasee, Italien'))
                ->push($this->draftResponse('Gardasee'))
                ->push($this->suggestionResponse('Algarve, Portugal'))
                ->push($this->draftResponse('Algarve')),
        ]);

        $this->artisan('regions:auto-generate', ['--limit' => 2])->assertSuccessful();

        $this->assertSame(2, Region::where('ai_generated', true)->count());
        $this->assertTrue(Region::where('name', 'Gardasee')->where('is_published', false)->exists());
        $this->assertTrue(Region::where('name', 'Algarve')->where('is_published', false)->exists());
    }

    public function test_stops_at_daily_cap_even_if_limit_is_higher(): void
    {
        $this->fakeApiKey();
        Region::create([
            'name' => 'Bereits heute erstellt', 'type' => 'Region', 'country' => 'Testland',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => false,
            'ai_generated' => true,
        ]);

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::sequence()
                ->push($this->suggestionResponse('Neuer Ort, Land'))
                ->push($this->draftResponse('Neuer Ort')),
        ]);

        $this->artisan('regions:auto-generate', ['--limit' => 5, '--daily-cap' => 1])->assertSuccessful();

        $this->assertSame(1, Region::where('ai_generated', true)->count());
    }

    public function test_skips_suggestion_that_already_exists_as_a_region(): void
    {
        $this->fakeApiKey();
        Region::create([
            'name' => 'Toskana', 'type' => 'Region', 'country' => 'Italien',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => true,
        ]);

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::sequence()
                ->push($this->suggestionResponse('Toskana')),
        ]);

        $this->artisan('regions:auto-generate', ['--limit' => 1])->assertSuccessful();

        $this->assertSame(1, Region::count());
        $this->assertSame(0, Region::where('ai_generated', true)->count());
    }

    public function test_does_nothing_when_openai_is_not_configured(): void
    {
        config(['services.openai.key' => null]);

        $this->artisan('regions:auto-generate')->assertSuccessful();

        $this->assertSame(0, Region::count());
    }
}

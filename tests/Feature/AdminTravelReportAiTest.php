<?php

namespace Tests\Feature;

use App\Models\TravelReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTravelReportAiTest extends TestCase
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

    private function report(): TravelReport
    {
        return TravelReport::create([
            'title' => 'Ein Wochenende auf Föhr',
            'excerpt' => 'Kurz',
            'content' => 'Platzhaltertext.',
            'author_name' => 'Anna',
        ]);
    }

    public function test_admin_can_generate_report_text_via_ai(): void
    {
        $this->fakeApiKey();
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => "Ein handgeschriebener Absatz.\n\n## Der Abend\n\nNoch ein Absatz."]],
                ],
            ], 200),
        ]);
        $report = $this->report();

        $response = $this->actingAs($this->admin())->post(route('admin.reports.ai-text', $report), [
            'ai_topic' => 'Ein Wochenende auf Föhr',
            'ai_context' => 'Winter, stürmisch',
        ]);

        $response->assertRedirect();
        $report->refresh();
        $this->assertStringContainsString('handgeschriebener Absatz', $report->content);
        $this->assertTrue($report->ai_generated);
    }

    public function test_ai_text_generation_failure_returns_validation_error_without_changing_content(): void
    {
        $this->fakeApiKey();
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response(['error' => ['message' => 'rate limited']], 429),
        ]);
        $report = $this->report();

        $response = $this->actingAs($this->admin())->post(route('admin.reports.ai-text', $report), [
            'ai_topic' => 'Ein Wochenende auf Föhr',
        ]);

        $response->assertSessionHasErrors('ai_topic');
        $this->assertSame('Platzhaltertext.', $report->fresh()->content);
    }

    public function test_admin_can_generate_a_cover_image_via_ai(): void
    {
        Storage::fake('public');
        $this->fakeApiKey();
        $fakeImage = base64_encode('fake-png-bytes');
        Http::fake([
            'api.openai.com/v1/images/generations' => Http::response([
                'data' => [['b64_json' => $fakeImage]],
            ], 200),
        ]);
        $report = $this->report();

        $response = $this->actingAs($this->admin())->post(route('admin.reports.ai-image', $report), [
            'ai_prompt' => 'Foto passend zum Bericht, professionelle Reisefotografie',
        ]);

        $response->assertRedirect();
        $this->assertSame(1, $report->media()->count());
        $this->assertTrue($report->media()->first()->is_cover);
    }
}

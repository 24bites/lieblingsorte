<?php

namespace Tests\Feature;

use App\Models\TravelReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelReportModelTest extends TestCase
{
    use RefreshDatabase;

    private function makeReport(array $overrides = []): TravelReport
    {
        return TravelReport::create(array_merge([
            'title' => 'Ein Wochenende auf Föhr',
            'excerpt' => 'Kurz und knackig',
            'content' => '<p>Ein Absatz zum Einstieg.</p><h2>Der erste Tag</h2><p>Es regnete den ganzen Vormittag.</p>',
            'author_name' => 'Anna',
        ], $overrides));
    }

    public function test_slug_is_generated_from_title(): void
    {
        $report = $this->makeReport();

        $this->assertSame('ein-wochenende-auf-foehr', $report->slug);
    }

    public function test_duplicate_titles_get_a_unique_slug(): void
    {
        $this->makeReport();
        $second = $this->makeReport();

        $this->assertSame('ein-wochenende-auf-foehr-2', $second->slug);
    }

    public function test_content_is_stored_and_returned_as_html(): void
    {
        $report = $this->makeReport();

        $this->assertStringContainsString('<h2>Der erste Tag</h2>', $report->content);
    }

    public function test_faq_is_cast_to_an_array(): void
    {
        $report = $this->makeReport([
            'faq' => [['question' => 'Wann ist beste Reisezeit?', 'answer' => 'Zwischen Mai und September.']],
        ]);

        $this->assertIsArray($report->fresh()->faq);
        $this->assertSame('Wann ist beste Reisezeit?', $report->fresh()->faq[0]['question']);
    }

    public function test_faq_json_ld_returns_null_without_faq(): void
    {
        $report = $this->makeReport();

        $this->assertNull($report->faqJsonLd());
    }

    public function test_faq_json_ld_builds_faq_page_schema(): void
    {
        $report = $this->makeReport([
            'faq' => [
                ['question' => 'Wann ist beste Reisezeit?', 'answer' => 'Zwischen Mai und September.'],
                ['question' => 'Gibt es Parkplätze?', 'answer' => 'Ja, mehrere bewirtschaftete Parkplätze.'],
            ],
        ]);

        $jsonLd = $report->faqJsonLd();

        $this->assertSame('FAQPage', $jsonLd['@type']);
        $this->assertCount(2, $jsonLd['mainEntity']);
        $this->assertSame('Wann ist beste Reisezeit?', $jsonLd['mainEntity'][0]['name']);
        $this->assertSame('Zwischen Mai und September.', $jsonLd['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function test_reading_time_is_computed_from_word_count(): void
    {
        $report = $this->makeReport(['content' => implode(' ', array_fill(0, 400, 'Wort'))]);

        $this->assertSame(2, $report->reading_time_minutes);
    }

    public function test_reading_time_is_at_least_one_minute(): void
    {
        $report = $this->makeReport(['content' => 'Kurzer Text.']);

        $this->assertSame(1, $report->reading_time_minutes);
    }

    public function test_published_scope_only_returns_published_reports(): void
    {
        $this->makeReport(['title' => 'Veröffentlicht', 'is_published' => true]);
        $this->makeReport(['title' => 'Entwurf', 'is_published' => false]);

        $this->assertSame(1, TravelReport::published()->count());
    }
}

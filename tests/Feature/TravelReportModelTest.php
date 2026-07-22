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
            'content' => "Ein Absatz zum Einstieg.\n\n## Der erste Tag\n\nEs regnete den ganzen Vormittag.\n\n## Der Abend\n\nWir haben Fisch gegessen.",
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

    public function test_content_blocks_splits_paragraphs_and_headings(): void
    {
        $report = $this->makeReport();

        $blocks = $report->contentBlocks();

        $this->assertSame([
            ['type' => 'paragraph', 'text' => 'Ein Absatz zum Einstieg.'],
            ['type' => 'heading', 'text' => 'Der erste Tag'],
            ['type' => 'paragraph', 'text' => 'Es regnete den ganzen Vormittag.'],
            ['type' => 'heading', 'text' => 'Der Abend'],
            ['type' => 'paragraph', 'text' => 'Wir haben Fisch gegessen.'],
        ], $blocks);
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

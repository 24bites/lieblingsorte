<?php

namespace Tests\Feature;

use App\Models\TravelReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelReportPublicPagesTest extends TestCase
{
    use RefreshDatabase;

    private function report(array $overrides = []): TravelReport
    {
        return TravelReport::create(array_merge([
            'title' => 'Ein Wochenende auf Föhr',
            'excerpt' => 'Ein ruhiges Winterwochenende auf der Insel.',
            'content' => '<p>Ein Absatz zum Einstieg.</p><h2>Der erste Tag</h2><p>Es regnete den ganzen Vormittag.</p>',
            'author_name' => 'Anna',
            'is_published' => true,
            'published_at' => now(),
        ], $overrides));
    }

    public function test_index_lists_published_reports(): void
    {
        $this->report();

        $response = $this->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('Ein Wochenende auf Föhr');
    }

    public function test_index_does_not_list_unpublished_reports(): void
    {
        $this->report(['title' => 'Entwurf', 'is_published' => false, 'published_at' => null]);

        $response = $this->get(route('reports.index'));

        $response->assertOk();
        $response->assertDontSee('Entwurf');
    }

    public function test_show_page_renders_published_report(): void
    {
        $report = $this->report();

        $response = $this->get(route('reports.show', $report));

        $response->assertOk();
        $response->assertSee('Ein Wochenende auf Föhr');
        $response->assertSee('Der erste Tag');
        $response->assertSee('Anna');
    }

    public function test_unpublished_report_is_404_for_guests(): void
    {
        $report = $this->report(['is_published' => false, 'published_at' => null]);

        $this->get(route('reports.show', $report))->assertNotFound();
    }

    public function test_show_page_has_seo_meta_and_article_json_ld(): void
    {
        $report = $this->report();

        $response = $this->get(route('reports.show', $report));

        $response->assertOk();
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('"@type":"Article"', false);
        $response->assertSee('"author":{"@type":"Person","name":"Anna"}', false);
    }

    public function test_show_page_falls_back_to_excerpt_for_seo_description_when_unset(): void
    {
        $report = $this->report(['seo_description' => null]);

        $response = $this->get(route('reports.show', $report));

        $response->assertOk();
        $response->assertSee('Ein ruhiges Winterwochenende auf der Insel.', false);
    }

    public function test_show_page_renders_content_as_raw_html(): void
    {
        $report = $this->report(['content' => '<h2>Anreise</h2><p>Mit der Fähre ab Dagebüll.</p>']);

        $response = $this->get(route('reports.show', $report));

        $response->assertOk();
        $response->assertSee('<h2>Anreise</h2>', false);
        $response->assertSee('Mit der Fähre ab Dagebüll.');
    }

    public function test_show_page_renders_faq_section_and_schema_when_present(): void
    {
        $report = $this->report([
            'faq' => [['question' => 'Ist die Insel autofrei?', 'answer' => 'Weitgehend, mit Ausnahmen für Anwohner.']],
        ]);

        $response = $this->get(route('reports.show', $report));

        $response->assertOk();
        $response->assertSee('Häufig gestellte Fragen');
        $response->assertSee('Ist die Insel autofrei?');
        $response->assertSee('"@type":"FAQPage"', false);
    }

    public function test_show_page_does_not_render_faq_schema_when_empty(): void
    {
        $report = $this->report(['faq' => null]);

        $response = $this->get(route('reports.show', $report));

        $response->assertOk();
        $response->assertDontSee('FAQPage', false);
    }

    public function test_show_page_uses_og_description_for_social_meta_when_set(): void
    {
        $report = $this->report([
            'seo_description' => 'Meta-Beschreibung für Suchmaschinen.',
            'og_description' => 'Einladender Text für Social-Media-Vorschauen.',
        ]);

        $response = $this->get(route('reports.show', $report));

        $response->assertOk();
        $response->assertSee('<meta property="og:description" content="Einladender Text für Social-Media-Vorschauen.">', false);
        $response->assertSee('<meta name="description" content="Meta-Beschreibung für Suchmaschinen.">', false);
    }
}

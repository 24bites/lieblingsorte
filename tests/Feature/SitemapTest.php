<?php

namespace Tests\Feature;

use App\Models\TravelReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_includes_published_reports(): void
    {
        $report = TravelReport::create([
            'title' => 'Ein Wochenende auf Föhr',
            'excerpt' => 'Kurz',
            'content' => 'Ein Absatz.',
            'author_name' => 'Anna',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee(route('reports.show', $report), false);
        $response->assertSee(route('reports.index'), false);
    }

    public function test_sitemap_excludes_unpublished_reports(): void
    {
        $report = TravelReport::create([
            'title' => 'Entwurf',
            'excerpt' => 'Kurz',
            'content' => 'Ein Absatz.',
            'author_name' => 'Anna',
            'is_published' => false,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertDontSee(route('reports.show', $report), false);
    }
}

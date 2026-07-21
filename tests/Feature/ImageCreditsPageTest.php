<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImageCreditsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_bildquellen_page_loads_successfully(): void
    {
        $response = $this->get('/bildquellen');

        $response->assertOk();
        $response->assertSee('Bildquellen');
    }

    public function test_impressum_links_to_bildquellen_page_instead_of_credits_md(): void
    {
        $response = $this->get('/impressum');

        $response->assertOk();
        $response->assertSee(route('legal.bildquellen'), false);
        $response->assertDontSee('CREDITS.md');
    }

    public function test_bildquellen_page_handles_missing_credits_file_gracefully(): void
    {
        $path = storage_path('app/credits.json');
        $backup = file_exists($path) ? file_get_contents($path) : null;

        if (file_exists($path)) {
            unlink($path);
        }

        try {
            $response = $this->get('/bildquellen');
            $response->assertOk();
            $response->assertSee('Aktuell sind keine Bildquellen hinterlegt');
        } finally {
            if ($backup !== null) {
                file_put_contents($path, $backup);
            }
        }
    }
}

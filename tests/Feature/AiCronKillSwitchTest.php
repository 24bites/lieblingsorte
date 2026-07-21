<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Support\AiCronSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiCronKillSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_crons_are_enabled_by_default(): void
    {
        $this->assertTrue(AiCronSettings::enabled());
    }

    public function test_disabling_the_switch_skips_images_ai_replace(): void
    {
        config(['services.openai.key' => 'test-key']);
        AiCronSettings::setEnabled(false);
        Http::fake();

        $this->artisan('images:ai-replace')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_disabling_the_switch_skips_regions_auto_generate(): void
    {
        config(['services.openai.key' => 'test-key']);
        AiCronSettings::setEnabled(false);
        Http::fake();

        $this->artisan('regions:auto-generate')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(0, Region::count());
    }
}

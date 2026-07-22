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

    public function test_both_ai_crons_are_enabled_by_default(): void
    {
        $this->assertTrue(AiCronSettings::enabled(AiCronSettings::IMAGES_AI_REPLACE));
        $this->assertTrue(AiCronSettings::enabled(AiCronSettings::REGIONS_AUTO_GENERATE));
    }

    public function test_default_interval_is_ten_minutes(): void
    {
        $this->assertSame(10, AiCronSettings::intervalMinutes(AiCronSettings::IMAGES_AI_REPLACE));
        $this->assertSame(10, AiCronSettings::intervalMinutes(AiCronSettings::REGIONS_AUTO_GENERATE));
    }

    public function test_interval_can_be_changed_independently_per_cron(): void
    {
        AiCronSettings::setIntervalMinutes(AiCronSettings::IMAGES_AI_REPLACE, 5);

        $this->assertSame(5, AiCronSettings::intervalMinutes(AiCronSettings::IMAGES_AI_REPLACE));
        $this->assertSame(10, AiCronSettings::intervalMinutes(AiCronSettings::REGIONS_AUTO_GENERATE));
    }

    public function test_interval_is_clamped_to_valid_cron_minute_range(): void
    {
        AiCronSettings::setIntervalMinutes(AiCronSettings::IMAGES_AI_REPLACE, 0);
        $this->assertSame(1, AiCronSettings::intervalMinutes(AiCronSettings::IMAGES_AI_REPLACE));

        AiCronSettings::setIntervalMinutes(AiCronSettings::IMAGES_AI_REPLACE, 500);
        $this->assertSame(59, AiCronSettings::intervalMinutes(AiCronSettings::IMAGES_AI_REPLACE));
    }

    public function test_disabling_images_ai_replace_does_not_affect_regions_auto_generate(): void
    {
        config(['services.openai.key' => 'test-key']);
        AiCronSettings::setEnabled(AiCronSettings::IMAGES_AI_REPLACE, false);

        $this->assertFalse(AiCronSettings::enabled(AiCronSettings::IMAGES_AI_REPLACE));
        $this->assertTrue(AiCronSettings::enabled(AiCronSettings::REGIONS_AUTO_GENERATE));
    }

    public function test_disabling_images_ai_replace_skips_the_command(): void
    {
        config(['services.openai.key' => 'test-key']);
        AiCronSettings::setEnabled(AiCronSettings::IMAGES_AI_REPLACE, false);
        Http::fake();

        $this->artisan('images:ai-replace')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_disabling_regions_auto_generate_skips_the_command(): void
    {
        config(['services.openai.key' => 'test-key']);
        AiCronSettings::setEnabled(AiCronSettings::REGIONS_AUTO_GENERATE, false);
        Http::fake();

        $this->artisan('regions:auto-generate')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(0, Region::count());
    }

    public function test_legacy_combined_switch_still_applies_until_a_per_cron_value_is_set(): void
    {
        \App\Models\Setting::set('ai_crons_enabled', '0');

        $this->assertFalse(AiCronSettings::enabled(AiCronSettings::IMAGES_AI_REPLACE));
        $this->assertFalse(AiCronSettings::enabled(AiCronSettings::REGIONS_AUTO_GENERATE));

        AiCronSettings::setEnabled(AiCronSettings::IMAGES_AI_REPLACE, true);
        $this->assertTrue(AiCronSettings::enabled(AiCronSettings::IMAGES_AI_REPLACE));
        $this->assertFalse(AiCronSettings::enabled(AiCronSettings::REGIONS_AUTO_GENERATE));
    }
}

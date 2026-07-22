<?php

use App\Support\AiCronSettings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The interval is read from admin settings on every artisan boot (not cached
// by config:cache), so changing it in Einstellungen takes effect on the next
// scheduler tick without a deploy - see AiCronSettings.
$imagesInterval = AiCronSettings::intervalMinutes(AiCronSettings::IMAGES_AI_REPLACE);
Schedule::command('images:ai-replace')->cron("*/{$imagesInterval} * * * *")->withoutOverlapping();

$regionsInterval = AiCronSettings::intervalMinutes(AiCronSettings::REGIONS_AUTO_GENERATE);
Schedule::command('regions:auto-generate')->cron("*/{$regionsInterval} * * * *")->withoutOverlapping();

$completeContentInterval = AiCronSettings::intervalMinutes(AiCronSettings::REGIONS_COMPLETE_CONTENT);
Schedule::command('regions:complete-content')->cron("*/{$completeContentInterval} * * * *")->withoutOverlapping();

<?php

namespace App\Support;

use App\Models\Setting;
use Throwable;

/**
 * Per-cron on/off switch and run interval for images:ai-replace and
 * regions:auto-generate, toggled from the admin settings page without
 * needing a deploy. Falls back to the legacy combined "ai_crons_enabled"
 * switch (pre-dating per-cron control) so an existing disabled state isn't
 * silently re-enabled by this change, and to safe defaults if the settings
 * table isn't reachable yet (e.g. routes/console.php loads on every artisan
 * call, including before the first migration).
 */
class AiCronSettings
{
    public const IMAGES_AI_REPLACE = 'images_ai_replace';

    public const REGIONS_AUTO_GENERATE = 'regions_auto_generate';

    public const REGIONS_COMPLETE_CONTENT = 'regions_complete_content';

    public const PINTEREST_CAPTIONS = 'pinterest_captions';

    private const LEGACY_KEY = 'ai_crons_enabled';

    private const DEFAULT_INTERVAL_MINUTES = 10;

    public static function enabled(string $cron): bool
    {
        $legacyDefault = self::read(self::LEGACY_KEY, '1');

        return self::read("{$cron}_enabled", $legacyDefault) === '1';
    }

    public static function setEnabled(string $cron, bool $enabled): void
    {
        Setting::set("{$cron}_enabled", $enabled ? '1' : '0');
    }

    public static function intervalMinutes(string $cron): int
    {
        $value = (int) self::read("{$cron}_interval_minutes", (string) self::DEFAULT_INTERVAL_MINUTES);

        return $value >= 1 && $value <= 59 ? $value : self::DEFAULT_INTERVAL_MINUTES;
    }

    public static function setIntervalMinutes(string $cron, int $minutes): void
    {
        Setting::set("{$cron}_interval_minutes", (string) max(1, min(59, $minutes)));
    }

    private static function read(string $key, string $default): string
    {
        try {
            return Setting::get($key, $default);
        } catch (Throwable) {
            return $default;
        }
    }
}

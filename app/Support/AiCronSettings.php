<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Kill switch for the images:ai-replace and regions:auto-generate crons,
 * toggled from the admin settings page without needing a deploy.
 */
class AiCronSettings
{
    private const KEY = 'ai_crons_enabled';

    public static function enabled(): bool
    {
        return Setting::get(self::KEY, '1') === '1';
    }

    public static function setEnabled(bool $enabled): void
    {
        Setting::set(self::KEY, $enabled ? '1' : '0');
    }
}

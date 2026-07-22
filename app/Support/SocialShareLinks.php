<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Builds the official "share intent" URLs each platform provides. None of
 * these need an API key or app registration - they open a pre-filled
 * share/compose dialog in the browser for a logged-in admin to review and
 * confirm, which is why this is the immediate, credential-free way to get
 * "one click -> matching post" working today. True unattended auto-posting
 * (no click at all) needs each platform's real API - see README/Einstellungen
 * for what that requires per platform.
 */
class SocialShareLinks
{
    public static function build(string $platform, string $url, string $caption, ?string $imageUrl = null): string
    {
        return match ($platform) {
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?'.http_build_query(['u' => $url]),
            'x' => 'https://twitter.com/intent/tweet?'.http_build_query(['text' => $caption, 'url' => $url]),
            'whatsapp' => 'https://wa.me/?'.http_build_query(['text' => "{$caption} {$url}"]),
            'telegram' => 'https://t.me/share/url?'.http_build_query(['url' => $url, 'text' => $caption]),
            'pinterest' => 'https://pinterest.com/pin/create/button/?'.http_build_query(array_filter([
                'url' => $url,
                'media' => $imageUrl,
                'description' => $caption,
            ])),
            default => throw new InvalidArgumentException("Unbekannte Plattform: {$platform}"),
        };
    }
}

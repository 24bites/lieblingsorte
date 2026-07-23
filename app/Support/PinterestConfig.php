<?php

namespace App\Support;

/**
 * Gate for the real Pinterest API connection (OAuth app + access token).
 * That connection step is deliberately deferred - pins are prepared and
 * queued for approval now, but nothing gets posted to Pinterest until this
 * returns true. Wire this up to real stored credentials once the Pinterest
 * Developer App/OAuth flow is set up.
 */
class PinterestConfig
{
    public static function isConfigured(): bool
    {
        return false;
    }
}

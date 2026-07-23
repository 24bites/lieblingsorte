<?php

namespace App\Console\Commands;

use App\Support\PinterestConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Keeps the Pinterest access token fresh so publishing never has to wait on
 * an interactive OAuth prompt. PinterestConfig::validAccessToken() already
 * refreshes on demand if a token is close to expiry, but running this daily
 * means a stale refresh token is caught (and logged) well before it would
 * otherwise block a real publish attempt.
 */
class RefreshPinterestToken extends Command
{
    protected $signature = 'pinterest:refresh-token';

    protected $description = 'Refresh the Pinterest OAuth access token if it is expiring soon';

    public function handle(): int
    {
        if (! PinterestConfig::isConfigured()) {
            $this->info('Pinterest ist nicht verbunden - überspringe.');

            return self::SUCCESS;
        }

        $token = PinterestConfig::validAccessToken();

        if ($token === null) {
            $this->error('Pinterest-Token konnte nicht erneuert werden - Verbindung muss neu hergestellt werden.');
            Log::warning('pinterest:refresh-token: Erneuerung fehlgeschlagen, Verbindung wirkt ungültig.');

            return self::FAILURE;
        }

        $this->info('Pinterest-Token ist gültig.');

        return self::SUCCESS;
    }
}

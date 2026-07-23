<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\PinterestApiClient;
use App\Support\PinterestConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class PinterestOAuthController extends Controller
{
    public function connect(Request $request)
    {
        if (! PinterestConfig::hasAppCredentials()) {
            return redirect()->route('admin.settings.edit')
                ->withErrors(['pinterest' => 'Bitte zuerst App-ID und App-Secret unter Einstellungen hinterlegen.']);
        }

        $state = Str::random(40);
        $request->session()->put('pinterest_oauth_state', $state);

        return redirect()->away(
            PinterestApiClient::authorizeUrl(PinterestConfig::appId(), self::redirectUri(), $state)
        );
    }

    public function callback(Request $request)
    {
        $expectedState = $request->session()->pull('pinterest_oauth_state');

        if ($request->filled('error')) {
            return redirect()->route('admin.settings.edit')
                ->withErrors(['pinterest' => 'Pinterest-Verbindung abgebrochen: '.$request->string('error_description')->toString()]);
        }

        if (blank($expectedState) || $request->string('state')->toString() !== $expectedState) {
            return redirect()->route('admin.settings.edit')
                ->withErrors(['pinterest' => 'Pinterest-Verbindung fehlgeschlagen: ungültiger State-Wert.']);
        }

        $code = $request->string('code')->toString();

        if (blank($code)) {
            return redirect()->route('admin.settings.edit')
                ->withErrors(['pinterest' => 'Pinterest-Verbindung fehlgeschlagen: kein Autorisierungscode erhalten.']);
        }

        try {
            $tokens = PinterestApiClient::exchangeCodeForToken(
                PinterestConfig::appId(),
                PinterestConfig::appSecret(),
                $code,
                self::redirectUri(),
            );

            PinterestConfig::storeTokens($tokens['access_token'], $tokens['refresh_token'], $tokens['expires_in']);
            PinterestConfig::storeAccountUsername(PinterestApiClient::fetchAccountUsername($tokens['access_token']));
        } catch (Throwable $e) {
            return redirect()->route('admin.settings.edit')
                ->withErrors(['pinterest' => 'Pinterest-Verbindung fehlgeschlagen: '.$e->getMessage()]);
        }

        return redirect()->route('admin.settings.edit')->with('status', 'Pinterest wurde erfolgreich verbunden.');
    }

    public function disconnect()
    {
        PinterestConfig::clearTokens();

        return redirect()->route('admin.settings.edit')->with('status', 'Pinterest-Verbindung wurde getrennt.');
    }

    private static function redirectUri(): string
    {
        return url('/admin/social-hub/pinterest/callback');
    }
}

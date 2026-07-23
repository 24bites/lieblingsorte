<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin, stateless wrapper around Pinterest's OAuth + v5 REST endpoints. Takes
 * credentials/tokens as explicit parameters rather than reading them itself,
 * so PinterestConfig (which owns storage/refresh orchestration) stays the
 * only place that knows where credentials live, and this class stays easy to
 * fake in tests via Http::fake().
 */
class PinterestApiClient
{
    private const AUTHORIZE_URL = 'https://www.pinterest.com/oauth/';

    private const API_BASE = 'https://api.pinterest.com/v5';

    private const SCOPES = 'boards:read,boards:write,pins:read,pins:write';

    public static function authorizeUrl(string $appId, string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'state' => $state,
        ]);

        return self::AUTHORIZE_URL.'?'.$query;
    }

    /**
     * @return array{access_token: string, refresh_token: ?string, expires_in: int}
     */
    public static function exchangeCodeForToken(string $appId, string $appSecret, string $code, string $redirectUri): array
    {
        $response = Http::asForm()
            ->withBasicAuth($appId, $appSecret)
            ->timeout(30)
            ->post(self::API_BASE.'/oauth/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ]);

        return self::tokenResponse($response);
    }

    /**
     * @return array{access_token: string, refresh_token: ?string, expires_in: int}
     */
    public static function refreshAccessToken(string $appId, string $appSecret, string $refreshToken): array
    {
        $response = Http::asForm()
            ->withBasicAuth($appId, $appSecret)
            ->timeout(30)
            ->post(self::API_BASE.'/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
            ]);

        return self::tokenResponse($response);
    }

    public static function fetchAccountUsername(string $accessToken): ?string
    {
        $response = Http::withToken($accessToken)->timeout(30)->get(self::API_BASE.'/user_account');

        if ($response->failed()) {
            return null;
        }

        return $response->json('username');
    }

    public static function createBoard(string $accessToken, string $name, ?string $description): string
    {
        $response = Http::withToken($accessToken)
            ->timeout(30)
            ->post(self::API_BASE.'/boards', array_filter([
                'name' => $name,
                'description' => $description,
            ]));

        if ($response->failed()) {
            throw new RuntimeException(
                'Pinterest-Board konnte nicht erstellt werden: '.$response->json('message', (string) $response->status())
            );
        }

        return (string) $response->json('id');
    }

    public static function createPin(string $accessToken, string $boardId, string $title, string $description, string $link, string $imageUrl): string
    {
        $response = Http::withToken($accessToken)
            ->timeout(30)
            ->post(self::API_BASE.'/pins', [
                'board_id' => $boardId,
                'title' => $title,
                'description' => $description,
                'link' => $link,
                'media_source' => [
                    'source_type' => 'image_url',
                    'url' => $imageUrl,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Pin konnte nicht erstellt werden: '.$response->json('message', (string) $response->status())
            );
        }

        return (string) $response->json('id');
    }

    private static function tokenResponse($response): array
    {
        if ($response->failed()) {
            throw new RuntimeException(
                'Pinterest-Token-Anfrage fehlgeschlagen: '.$response->json('message', (string) $response->status())
            );
        }

        return [
            'access_token' => $response->json('access_token'),
            'refresh_token' => $response->json('refresh_token'),
            'expires_in' => (int) $response->json('expires_in', 0),
        ];
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Manages the Bakong Open API Bearer token lifecycle.
 *
 * Token is obtained via POST /v1/auth/login (with the registered merchant email),
 * cached until (expires_in - margin) seconds, and transparently refreshed on the
 * next getToken() call after the cache expires.
 *
 * Fake mode (BAKONG_FAKE_MODE=true or no base URL set): returns a static fake token
 * and logs instead of making live API calls — safe for local/dev environments.
 */
class BakongTokenService
{
    private const CACHE_KEY    = 'bakong:api_token';
    private const RENEW_MARGIN = 120; // seconds before expiry to proactively refresh

    public function getToken(): string
    {
        if ($this->isFakeMode()) {
            return 'fake-bakong-token';
        }

        return Cache::get(self::CACHE_KEY) ?? $this->requestToken();
    }

    /**
     * Force a new token regardless of cache state — call this on a 401 response
     * so the next poll attempt uses fresh credentials.
     */
    public function forceRenew(): string
    {
        if ($this->isFakeMode()) {
            return 'fake-bakong-token';
        }

        Cache::forget(self::CACHE_KEY);
        return $this->requestToken();
    }

    private function requestToken(): string
    {
        $baseUrl = config('services.bakong.base_url');
        $email   = config('services.bakong.email');

        Log::info('Bakong: requesting access token', ['email' => $email]);

        $response = Http::post("{$baseUrl}/v1/auth/login", ['email' => $email]);

        if (!$response->successful() || ($response->json('responseCode') !== 0)) {
            Log::error('Bakong: token request failed', [
                'status'       => $response->status(),
                'responseCode' => $response->json('responseCode'),
            ]);
            throw new \RuntimeException('Bakong token request failed: ' . $response->body());
        }

        $token     = $response->json('data.token');
        $expiresIn = (int) ($response->json('data.expiresIn') ?? 7200);
        $cacheTtl  = max($expiresIn - self::RENEW_MARGIN, 60);

        Cache::put(self::CACHE_KEY, $token, $cacheTtl);

        Log::info("Bakong: token obtained, cached for {$cacheTtl}s");
        return $token;
    }

    private function isFakeMode(): bool
    {
        return config('services.bakong.fake_mode', false)
            || !config('services.bakong.base_url')
            || !config('services.bakong.email');
    }
}

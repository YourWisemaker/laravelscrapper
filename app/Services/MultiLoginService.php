<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MultiLoginService
{
    private const API_BASE_URL = 'http://localhost:35000/api/v1';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get the MultiLogin API token from configuration
     */
    private function getApiToken(): string
    {
        return config('services.multilogin.api_token');
    }

    /**
     * Make an authenticated request to the MultiLogin API
     */
    private function request(string $method, string $endpoint, array $data = [])
    {
        $url = self::API_BASE_URL . $endpoint;
        $token = $this->getApiToken();

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->$method($url, $data);

            if (!$response->successful()) {
                Log::error('MultiLogin API request failed', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('MultiLogin API request error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get all browser profiles
     */
    public function getProfiles(): array
    {
        $cacheKey = 'multilogin_profiles';

        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $response = $this->request('get', '/profile');
            return $response['data'] ?? [];
        });
    }

    /**
     * Create a new browser profile for OnlyFans scraping
     */
    public function createProfile(string $name): ?string
    {
        $response = $this->request('post', '/profile', [
            'name' => $name,
            'browser' => 'mimic',
            'os' => 'win',
            'navigator' => [
                'language' => 'en-US',
                'userAgent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'resolution' => '1920x1080',
                'platform' => 'Win32',
            ],
        ]);

        if (!$response || !isset($response['data']['uuid'])) {
            return null;
        }

        // Clear profiles cache
        Cache::forget('multilogin_profiles');

        return $response['data']['uuid'];
    }

    /**
     * Start a browser session with the specified profile
     */
    public function startBrowserSession(string $profileId): ?string
    {
        $response = $this->request('post', "/profile/{$profileId}/start");

        if (!$response || !isset($response['data']['wsEndpoint'])) {
            return null;
        }

        return $response['data']['wsEndpoint'];
    }

    /**
     * Stop a browser session
     */
    public function stopBrowserSession(string $profileId): bool
    {
        $response = $this->request('post', "/profile/{$profileId}/stop");
        return $response !== null;
    }

    /**
     * Get a profile by ID
     */
    public function getProfile(string $profileId): ?array
    {
        $response = $this->request('get', "/profile/{$profileId}");
        return $response['data'] ?? null;
    }

    /**
     * Delete a profile
     */
    public function deleteProfile(string $profileId): bool
    {
        $response = $this->request('delete', "/profile/{$profileId}");
        
        if ($response !== null) {
            // Clear profiles cache
            Cache::forget('multilogin_profiles');
            return true;
        }
        
        return false;
    }
}
<?php

namespace App\Services;

use App\Models\OnlyFansProfile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OnlyFansScraperService
{
    private const BASE_URL = 'https://onlyfans.com/api/v2';
    private const RATE_LIMIT_DELAY = 2; // seconds between requests

    public function scrapeProfile(string $username): ?OnlyFansProfile
    {
        try {
            // Simulate API request (replace with actual OnlyFans API endpoint when available)
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->get(self::BASE_URL . '/users/' . $username);

            if (!$response->successful()) {
                Log::error('Failed to fetch OnlyFans profile', [
                    'username' => $username,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();

            return OnlyFansProfile::updateOrCreate(
                ['username' => $username],
                [
                    'name' => $data['name'] ?? null,
                    'bio' => $data['bio'] ?? null,
                    'likes_count' => $data['likes_count'] ?? 0,
                    'avatar_url' => $data['avatar_url'] ?? null,
                    'cover_url' => $data['cover_url'] ?? null,
                    'posts_count' => $data['posts_count'] ?? 0,
                    'followers_count' => $data['followers_count'] ?? 0,
                    'following_count' => $data['following_count'] ?? 0,
                    'last_scraped_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error scraping OnlyFans profile', [
                'username' => $username,
                'error' => $e->getMessage(),
            ]);
            return null;
        }

        sleep(self::RATE_LIMIT_DELAY);
    }

    public function shouldScrapeProfile(OnlyFansProfile $profile): bool
    {
        if (!$profile->last_scraped_at) {
            return true;
        }

        $hoursToWait = $profile->likes_count >= 100000 ? 24 : 72;
        return $profile->last_scraped_at->addHours($hoursToWait)->isPast();
    }
}
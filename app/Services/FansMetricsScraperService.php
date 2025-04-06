<?php

namespace App\Services;

use App\Models\OnlyFansProfile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class FansMetricsScraperService
{
    private const RATE_LIMIT_DELAY = 2; // seconds between requests

    /**
     * Get the base URL for FansMetrics
     *
     * @return string
     */
    private function getBaseUrl(): string
    {
        return config('services.fansmetrics.base_url', 'https://fansmetrics.com') . '/search';
    }

    /**
     * Get the minimum likes threshold
     *
     * @return int
     */
    private function getMinLikes(): int
    {
        return config('services.fansmetrics.min_likes', 100000);
    }

    /**
     * Scrape profiles from FansMetrics with likes over 100k
     *
     * @param int $limit Maximum number of profiles to scrape
     * @return array Array of scraped OnlyFansProfile models
     */
    public function scrapeProfiles(int $limit): array
    {
        try {
            Log::info('Starting to scrape profiles from FansMetrics.com');
            
            // Add more realistic browser headers to avoid detection
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
                'Cache-Control' => 'max-age=0',
            ])->get($this->getBaseUrl(), [
                'sort' => 'likes'
            ]);

            if (!$response->successful()) {
                Log::error('Failed to fetch FansMetrics data', [
                    'status' => $response->status(),
                    'reason' => $response->reason(),
                ]);
                return [];
            }

            $html = $response->body();
            Log::debug('Received HTML response of length: ' . strlen($html));
            
            // Save HTML for debugging if needed
            // file_put_contents(storage_path('logs/fansmetrics_response.html'), $html);
            
            $profiles = $this->parseProfilesFromHtml($html, $limit);
            Log::info('Parsed ' . count($profiles) . ' profiles from HTML');
            
            $savedProfiles = [];
            foreach ($profiles as $profileData) {
                if ($profileData['likes_count'] >= $this->getMinLikes()) {
                    $profile = $this->saveProfile($profileData);
                    if ($profile) {
                        $savedProfiles[] = $profile;
                        Log::info('Saved profile: @' . $profileData['username']);
                    }
                    sleep(self::RATE_LIMIT_DELAY);
                }
            }

            return $savedProfiles;
        } catch (\Exception $e) {
            Log::error('Error scraping FansMetrics profiles', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    /**
     * Parse profiles from HTML content
     *
     * @param string $html HTML content from FansMetrics
     * @param int $limit Maximum number of profiles to parse
     * @return array Array of profile data
     */
    private function parseProfilesFromHtml(string $html, int $limit): array
    {
        $profiles = [];
        $dom = new DOMDocument();
        // Suppress warnings from malformed HTML
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        Log::debug('HTML content length: ' . strlen($html));
        
        // Find all creator cards - these contain both the creator info and bio
        $creatorCards = $xpath->query('//div[contains(@class, "creator-card")]');
        Log::debug('Found creator cards: ' . $creatorCards->length);
        
        $count = 0;
        foreach ($creatorCards as $card) {
            if ($count >= $limit) break;
            
            // Find the creator-info link within this card
            $creatorInfoElement = $xpath->query('.//a[contains(@class, "creator-info")]', $card)->item(0);
            if (!$creatorInfoElement) {
                Log::debug('Creator info element not found in card');
                continue;
            }

            // Check for 'Ad' label and skip if present
            $adElement = $xpath->query('.//div[contains(@class, "text-black") and contains(text(), "Ad")]', $card)->item(0);
            if ($adElement) {
                Log::debug('Ad label found, skipping profile');
                continue;
            }

            // Extract username using the exact structure from the example
            $usernameElement = $xpath->query('.//span[contains(@class, "creator-username")]', $creatorInfoElement)->item(0);
            if (!$usernameElement) {
                Log::debug('Username element not found');
                continue;
            }

            $usernameText = $usernameElement->textContent;
            // Clean up username (remove @ symbol and any whitespace)
            $username = trim(str_replace('@', '', $usernameText));
            Log::debug('Extracted username: ' . $username);

            // Extract name from the breadcrumbs element
            $nameElement = $xpath->query('.//h5[contains(@class, "creator-breadcrumbs")]', $creatorInfoElement)->item(0);
            $name = $nameElement ? trim($nameElement->textContent) : null;
            if ($name) {
                // Remove "Ad" text if present
                $name = trim(str_replace('Ad', '', $name));
            }
            Log::debug('Extracted name: ' . ($name ?? 'null'));

            // Find the bio element within this card
            $bioElement = $xpath->query('.//div[contains(@class, "creator-bio")]', $card)->item(0);
            $bio = null;
            if ($bioElement) {
                $bio = trim($bioElement->textContent);
                Log::debug('Extracted bio: ' . (substr($bio, 0, 30) . '...'));
            }

            // Try to extract likes count if available
            $likesElement = $xpath->query('.//div[contains(@class, "creator-likes")]', $card)->item(0);
            $likesCount = $this->getMinLikes(); // Default to minimum
            if ($likesElement) {
                $likesText = $likesElement->textContent;
                // Extract number from text like "8,229,197"
                if (preg_match('/([\d,.]+)/', $likesText, $matches)) {
                    $number = str_replace(',', '', $matches[1]);
                    $likesCount = (int)$number;
                    Log::debug('Extracted likes count: ' . $likesCount);
                }
            }

            // Only include profiles with likes count above the minimum
            if ($likesCount >= $this->getMinLikes()) {
                $profiles[] = [
                    'username' => $username,
                    'name' => $name,
                    'bio' => $bio,
                    'likes_count' => $likesCount,
                ];
                $count++;
                Log::info("Found profile: @{$username} with {$likesCount} likes");
            }
        }

        return $profiles;
    }

    /**
     * Save profile data to database
     *
     * @param array $profileData Profile data
     * @return OnlyFansProfile|null
     */
    private function saveProfile(array $profileData): ?OnlyFansProfile
    {
        try {
            return OnlyFansProfile::updateOrCreate(
                ['username' => $profileData['username']],
                [
                    'name' => $profileData['name'],
                    'bio' => $profileData['bio'],
                    'likes_count' => $profileData['likes_count'],
                    'last_scraped_at' => now(),
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error saving OnlyFans profile', [
                'username' => $profileData['username'],
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Determine if a profile should be scraped based on last scrape time
     *
     * @param OnlyFansProfile $profile
     * @return bool
     */
    public function shouldScrapeProfile(OnlyFansProfile $profile): bool
    {
        if (!$profile->last_scraped_at) {
            return true;
        }

        $hoursToWait = $profile->likes_count >= 100000 ? 24 : 72;
        return $profile->last_scraped_at->addHours($hoursToWait)->isPast();
    }
}
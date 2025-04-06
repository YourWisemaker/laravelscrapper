<?php

namespace App\Jobs;

use App\Models\OnlyFansProfile;
use App\Services\OnlyFansScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeOnlyFansProfile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $username,
    ) {}

    public function handle(OnlyFansScraperService $scraperService): void
    {
        $profile = OnlyFansProfile::firstOrNew(['username' => $this->username]);

        if (!$scraperService->shouldScrapeProfile($profile)) {
            return;
        }

        $scraperService->scrapeProfile($this->username);
    }

    public function tags(): array
    {
        return ['onlyfans', 'profile-scraping', $this->username];
    }

    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }
}
<?php

namespace App\Jobs;

use App\Services\FansMetricsScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeFansMetricsProfiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param int $limit Maximum number of profiles to scrape
     */
    public function __construct(
        private readonly int $limit = 50,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FansMetricsScraperService $scraperService): void
    {
        $scraperService->scrapeProfiles($this->limit);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['onlyfans', 'fansmetrics', 'profile-scraping'];
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }
}
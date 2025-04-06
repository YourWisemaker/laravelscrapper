<?php

namespace App\Console\Commands;

use App\Services\FansMetricsScraperService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScrapeFansMetrics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:fansmetrics {--limit=50 : Maximum number of profiles to scrape}'; 

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape OnlyFans profiles from FansMetrics.com with over 100k likes';

    /**
     * Execute the console command.
     */
    public function handle(FansMetricsScraperService $scraperService)
    {
        $limit = (int) $this->option('limit');
        
        $this->info("Starting to scrape FansMetrics.com for OnlyFans profiles with over 100k likes...");
        $this->info("Limit set to: {$limit} profiles");
        
        try {
            $profiles = $scraperService->scrapeProfiles($limit);
            
            $count = count($profiles);
            $this->info("Successfully scraped {$count} profiles.");
            
            foreach ($profiles as $profile) {
                $this->line("- @{$profile->username}: {$profile->name}");
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error scraping profiles: {$e->getMessage()}");
            Log::error('Command scrape:fansmetrics failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return 1;
        }
    }
}
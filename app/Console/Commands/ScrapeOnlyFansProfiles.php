<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeOnlyFansProfile;
use App\Models\OnlyFansProfile;
use Illuminate\Console\Command;

class ScrapeOnlyFansProfiles extends Command
{
    protected $signature = 'onlyfans:scrape-profiles';
    protected $description = 'Scrape OnlyFans profiles based on their likes count';

    public function handle(): void
    {
        // Scrape profiles with over 100k likes (every 24 hours)
        OnlyFansProfile::where('likes_count', '>=', 100000)
            ->cursor()
            ->each(function ($profile) {
                ScrapeOnlyFansProfile::dispatch($profile->username)
                    ->onQueue('high-priority');
            });

        // Scrape other profiles (every 72 hours)
        OnlyFansProfile::where('likes_count', '<', 100000)
            ->cursor()
            ->each(function ($profile) {
                ScrapeOnlyFansProfile::dispatch($profile->username)
                    ->onQueue('low-priority');
            });

        $this->info('Profile scraping jobs have been dispatched.');
    }
}
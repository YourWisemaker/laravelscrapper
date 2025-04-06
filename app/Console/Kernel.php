<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Run profile scraping command every hour
        $schedule->command('onlyfans:scrape-profiles')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();
            
        // Run FansMetrics scraper daily
        $schedule->command('scrape:fansmetrics --limit=100')
            ->daily()
            ->withoutOverlapping()
            ->onOneServer()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
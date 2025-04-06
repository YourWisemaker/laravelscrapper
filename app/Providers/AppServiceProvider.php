<?php

namespace App\Providers;

use App\Services\FansMetricsScraperService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FansMetricsScraperService::class, function ($app) {
            return new FansMetricsScraperService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

<?php

namespace App\Providers;

use App\Services\DashboardService;
use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DashboardService::class, function ($app) {
            return new DashboardService();
        });
    }

    public function boot(): void
    {
        //
    }
}
<?php

namespace App\Providers;

use App\Services\LocationIQService;
use Illuminate\Support\ServiceProvider;

class LocationIQServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(LocationIQService::class, function ($app) {
            return new LocationIQService();
        });
    }

    public function boot()
    {
        
    }
}

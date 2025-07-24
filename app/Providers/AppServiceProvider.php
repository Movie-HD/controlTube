<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\MovieLink;
use App\Observers\MovieLinkObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        MovieLink::observe(MovieLinkObserver::class);
    }
}

<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

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
        // Laravel 8+ defaults to Tailwind pagination markup; the views were
        // built for Laravel 6's Bootstrap 4 links.
        Paginator::useBootstrapFour();
    }
}

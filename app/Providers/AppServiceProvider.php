<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\Paginator;

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
        // Pagination styling (optional - Bootstrap)
        Paginator::useBootstrap();

        // 🔥 Load all helper files automatically
        foreach (glob(app_path('Helpers/*.php')) as $file) {
            require_once $file;
        }
    }
}
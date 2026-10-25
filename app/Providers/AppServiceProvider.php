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
        // Force HTTPS scheme in production or when APP_URL is HTTPS
        if (config('app.env') === 'production' || str_starts_with(config('app.url') ?? '', 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Pagination styling (optional - Bootstrap)
        Paginator::useBootstrap();

        // 🔥 Load all helper files automatically
        foreach (glob(app_path('Helpers/*.php')) as $file) {
            require_once $file;
        }

        // Bypasses SSL certificate verification locally to prevent cURL error 60
        if (config('app.env') === 'local' || app()->environment('local') || (!app()->runningInConsole() && in_array(request()->getHost(), ['127.0.0.1', 'localhost']))) {
            Http::globalOptions([
                'verify' => false,
            ]);
        }
    }
}
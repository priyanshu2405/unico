<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;

class LocationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }


    public function boot()
    {
        if (app()->runningInConsole()) {
            return;
        }

        // ✅ STEP 1: Agar session already hai → kuch mat karo
        if (
            session()->has('user_country_code') &&
            session()->has('user_timezone') &&
            session()->has('user_currency')
        ) {
            return;
        }

        // ✅ STEP 2: API call (sirf first time)
        try {
            $response = Http::timeout(5)->get('https://ipapi.co/json/');



            // ✅ STEP 3: Data nikalo
            $data = $response->json();

            // ✅ STEP 4: Session set
            session([
                'user_country_code' => $data['country_code'] ?? 'IN',
                'user_timezone'     => $data['timezone'] ?? 'Asia/Kolkata',
                'user_currency'     => $data['currency'] ?? 'INR',
            ]);
        } catch (\Exception $e) {
            // Fallback
            session([
                'user_country_code' => 'IN',
                'user_timezone'     => 'Asia/Kolkata',
                'user_currency'     => 'INR',
            ]);
        }
    }
}

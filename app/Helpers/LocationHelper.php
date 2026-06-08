<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use App\Models\Bank;

class LocationHelper
{
    /* ===============================
       1. Detect Country via IP
    ================================ */
    public static function geo()
    {
        $ip = request()->header('X-Forwarded-For')
            ?? request()->header('CF-Connecting-IP')
            ?? request()->ip();

        // Local testing ke liye:
        // $ip = '8.8.8.8';

        try {
            $response = Http::timeout(3)->get("https://ipapi.co/{$ip}/json/");
            $data = $response->json();
        } catch (\Exception $e) {
            $data = [];
        }

        $geo = [
            'country_code' => $data['country'] ?? 'IN',
            'country_name' => $data['country_name'] ?? 'India',
            'currency'     => $data['currency'] ?? 'INR',
            'flag'         => strtolower($data['country'] ?? 'in'),
        ];

        session(['geo' => $geo]);

        return $geo;
    }



    /* ===============================
       2. Currency Code
    ================================ */
    public static function currency()
    {
        return self::geo()['currency'];
    }

    /* ===============================
       3. Flag URL
    ================================ */
    public static function flag()
    {
        $code = self::geo()['flag'];
        return "https://flagcdn.com/w40/{$code}.png";
    }

    /* ===============================
       4. Price Convert
    ================================ */
    public static function price($amount)
    {
        $rates = [
            'INR' => 1,
            'USD' => 0.012,
            'AED' => 0.044,
            'EUR' => 0.011,
        ];

        $currency = self::currency();
        $rate = $rates[$currency] ?? 1;

        return round($amount * $rate, 2);
    }

    /* ===============================
       5. Country-wise Banks
    ================================ */
    public static function banks()
    {
        $country = self::geo()['country_code'];
        return Bank::where('country_code', $country)->get();
    }

    /* ===============================
       6. Bank Validation Rules
    ================================ */
    public static function bankRules()
    {
        return self::geo()['country_code'] === 'IN'
            ? ['ifsc_code' => 'required']
            : ['swift_code' => 'required'];
    }
}

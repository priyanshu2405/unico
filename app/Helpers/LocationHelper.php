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
        if (auth()->check()) {
            $user = auth()->user();
            $countryIso = strtoupper(trim($user->country_iso ?? ''));
            if (!empty($countryIso)) {
                $countryData = CountryHelper::getCountryData($countryIso);
                return [
                    'country_code' => $countryIso,
                    'country_name' => $user->country ?? ($countryData['country_name'] ?? $countryIso),
                    'currency'     => $user->currency,
                    'flag'         => strtolower($countryIso),
                ];
            }
        }

        $ip = request()->header('X-Forwarded-For')
            ?? request()->header('CF-Connecting-IP')
            ?? request()->ip();

        // If X-Forwarded-For contains multiple comma-separated IPs, take the first one
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        try {
            $response = Http::timeout(3)->get("https://ipapi.co/{$ip}/json/");
            $data = $response->json();
            if (isset($data['error']) && $data['error']) {
                $data = [];
            }
        } catch (\Exception $e) {
            $data = [];
        }

        $geo = [
            'country_code' => $data['country'] ?? 'US',
            'country_name' => $data['country_name'] ?? 'United States',
            'currency'     => $data['currency'] ?? 'USD',
            'flag'         => strtolower($data['country'] ?? 'us'),
        ];

        session(['geo' => $geo]);

        return $geo;
    }



    /* ===============================
       2. Currency Code
    ================================ */
    public static function currency()
    {
        if (auth()->check() && !empty(auth()->user()->currency)) {
            return auth()->user()->currency;
        }
        return self::geo()['currency'] ?? 'USD';
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

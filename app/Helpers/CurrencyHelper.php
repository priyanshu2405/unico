<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class CurrencyHelper
{
public static function convert($amount, $from, $to)
{
    $from = strtoupper(trim($from));
    $to = strtoupper(trim($to));

    if (!$from || !$to) {
        throw new \Exception("Currency missing");
    }

    $response = Http::get("https://open.er-api.com/v6/latest/{$from}");

    if ($response->failed()) {
        throw new \Exception("HTTP Error: " . $response->status());
    }

    $data = $response->json();

    if (($data['result'] ?? '') !== 'success') {
        throw new \Exception("API Error: " . json_encode($data));
    }

    if (!isset($data['rates'][$to])) {
        throw new \Exception("Currency not supported: {$to}");
    }

    return $amount * $data['rates'][$to];
}
}
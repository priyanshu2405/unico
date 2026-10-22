<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    /**
     * Default fallback rates if database table is not yet seeded or query fails.
     */
    protected static array $defaultRates = [
        'USD' => 1.000000,
        'INR' => 0.012000,
        'PKR' => 0.003600,
        'GBP' => 1.280000,
        'EUR' => 1.080000,
        'AED' => 0.272000,
        'TRY' => 0.030000,
        'NPR' => 0.007500,
    ];

    /**
     * Mapping of legacy/dirty currency inputs to standard ISO 4217 codes.
     */
    protected static array $legacyAliases = [
        'IN'      => 'INR',
        'RS'      => 'PKR',
        'NEAPLEE' => 'NPR',
        'NEPAL'   => 'NPR',
        'UK'      => 'GBP',
        'US'      => 'USD',
    ];

    /**
     * Normalize currency code to standard ISO uppercase string.
     */
    public static function normalizeCurrencyCode(?string $currency): string
    {
        if (empty($currency)) {
            return 'USD';
        }

        $clean = strtoupper(trim($currency));

        return self::$legacyAliases[$clean] ?? $clean;
    }

    /**
     * Get system base currency (defaults to 'USD').
     */
    public static function getBaseCurrency(): string
    {
        try {
            $setting = SystemSetting::where('settings_key', 'base_currency')->value('settings_value');
            if (!empty($setting)) {
                $configured = self::normalizeCurrencyCode($setting);
                if ($configured === 'USD') {
                    return $configured;
                }

                Log::warning("Ignoring unsupported base currency {$configured}; accounting base is fixed to USD.");
            }
        } catch (\Throwable $e) {
            // Fallback during initial installation or DB unavailability
        }

        return 'USD';
    }

    /**
     * Get the active conversion rate from source currency to target base currency.
     */
    public static function getExchangeRate(string $fromCurrency, ?string $toCurrency = null): float
    {
        $from = self::normalizeCurrencyCode($fromCurrency);
        $to   = self::normalizeCurrencyCode($toCurrency ?? self::getBaseCurrency());

        if ($from === $to) {
            return 1.000000;
        }

        try {
            $rate = ExchangeRate::where('from_currency', $from)
                ->where('to_currency', $to)
                ->where('is_active', true)
                ->value('rate');

            if ($rate !== null && (float)$rate > 0) {
                return (float)$rate;
            }
        } catch (\Throwable $e) {
            try {
                \Illuminate\Support\Facades\Log::warning("CurrencyService: Failed to query exchange_rates table: " . $e->getMessage());
            } catch (\Throwable $logEx) {}
        }

        // Check inverse if converting from USD to another currency
        if ($from === 'USD' && $to !== 'USD') {
            try {
                $inverseRate = ExchangeRate::where('from_currency', $to)
                    ->where('to_currency', 'USD')
                    ->where('is_active', true)
                    ->value('rate');

                if ($inverseRate !== null && (float)$inverseRate > 0) {
                    return round(1.0 / (float)$inverseRate, 6);
                }
            } catch (\Throwable $e) {}

            if (isset(self::$defaultRates[$to]) && (float)self::$defaultRates[$to] > 0) {
                return round(1.0 / (float)self::$defaultRates[$to], 6);
            }
        }

        // Check in default static fallback array
        if (isset(self::$defaultRates[$from]) && $to === 'USD') {
            return (float)self::$defaultRates[$from];
        }

        // Cross-currency conversion via base currency (e.g. PKR -> USD -> INR)
        $base = self::getBaseCurrency();
        if ($from !== $base && $to !== $base) {
            $rateFromToBase = self::getExchangeRate($from, $base);
            $rateBaseToTarget = self::getExchangeRate($base, $to);
            if ($rateFromToBase > 0 && $rateBaseToTarget > 0) {
                return round($rateFromToBase * $rateBaseToTarget, 6);
            }
        }

        try {
            \Illuminate\Support\Facades\Log::error("CurrencyService: CRITICAL — No exchange rate for {$from} -> {$to}. Returning 0 to prevent silent corruption.");
        } catch (\Throwable $logEx) {}
        return 0.0;
    }

    /**
     * Alias for getExchangeRate to base USD.
     */
    public static function getRate(?string $currency): float
    {
        return self::getExchangeRate($currency ?? 'USD', self::getBaseCurrency());
    }

    /**
     * Convert an amount from given currency to system base currency (USD).
     *
     * @return array{amount: float, currency: string, rate: float, exchange_rate: float, base_amount: float, base_currency: string}
     */
    public static function convertToBase(float $amount, ?string $currency): array
    {
        $cleanCurrency = self::normalizeCurrencyCode($currency);
        $baseCurrency  = self::getBaseCurrency();
        $rate          = self::getExchangeRate($cleanCurrency, $baseCurrency);
        $baseAmount    = round($amount * $rate, 4);


        return [
            'amount'        => (float)$amount,
            'currency'      => $cleanCurrency,
            'rate'          => (float)$rate,
            'exchange_rate' => (float)$rate,
            'base_amount'   => (float)$baseAmount,
            'base_currency' => $baseCurrency,
        ];
    }

    /**
     * Direct conversion helper between any two currencies.
     */
    public static function convert(float $amount, string $from, string $to = 'USD'): float
    {
        $rate = self::getExchangeRate($from, $to);
        return round($amount * $rate, 2);
    }

    public static function convertOrderAmountToBase($order, float $amount): float
    {
        $lockedRate = (float) ($order->exchange_rate_applied ?? 0);
        if ($lockedRate > 0) {
            return round($amount * $lockedRate, 2);
        }

        return self::convertToBase($amount, $order->currency ?: 'USD')['base_amount'];
    }

    /**
     * Get order amount in base currency (USD), preferring the saved transaction snapshot.
     */
    public static function getOrderBaseAmount($order): float
    {
        if ($order->amount_in_base_currency !== null && (float)$order->amount_in_base_currency > 0) {
            return (float)$order->amount_in_base_currency;
        }

        return self::convertOrderAmountToBase($order, (float) $order->amount);
    }

    /**
     * Check if a currency is configured in active exchange rates or is the system base currency.
     */
    public static function isCurrencyConfigured(?string $currency): bool
    {
        if (empty($currency)) {
            return false;
        }
        $code = self::normalizeCurrencyCode($currency);
        $base = self::getBaseCurrency();
        if ($code === $base) {
            return true;
        }

        try {
            return ExchangeRate::where('from_currency', $code)
                ->where('to_currency', $base)
                ->where('is_active', true)
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get all active exchange rates mapped to base currency for front-end usage.
     * Only includes rates explicitly configured by Admin in the database + base currency.
     *
     * @return array<string, float>
     */
    public static function getAllActiveRates(): array
    {
        $base = self::getBaseCurrency();
        $rates = [$base => 1.0]; // Base currency is always 1.0

        try {
            $dbRates = ExchangeRate::where('to_currency', $base)
                ->where('is_active', true)
                ->pluck('rate', 'from_currency')
                ->toArray();
            foreach ($dbRates as $curr => $rate) {
                $rates[self::normalizeCurrencyCode($curr)] = (float)$rate;
            }
        } catch (\Throwable $e) {}

        return $rates;
    }
}

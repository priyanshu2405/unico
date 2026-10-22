<?php

namespace App\Services;

use App\Models\InventoryVoucher;
use App\Models\Order;
use App\Models\RefundRequest;

class ProfitLossService
{
    public static function getRefundRatio(Order $order, RefundRequest $refund): float
    {
        $orderAmount = (float) ($order->amount ?? 0);
        $refundAmount = (float) ($refund->amount ?? 0);

        if ($orderAmount <= 0 || $refundAmount <= 0) {
            return 0.0;
        }

        return min(1.0, $refundAmount / $orderAmount);
    }

    public static function getActiveSoldQuantity(int $grossSoldQuantity): int
    {
        return max(0, $grossSoldQuantity);
    }

    public static function getRefundBaseAmount(RefundRequest $refund): float
    {
        $order = $refund->order;

        if ($refund->amount_in_base_currency !== null && (float) $refund->amount_in_base_currency > 0) {
            return (float) $refund->amount_in_base_currency;
        }

        if ($order && $order->amount_in_base_currency !== null && (float) $order->amount_in_base_currency > 0) {
            return (float) $order->amount_in_base_currency * self::getRefundRatio($order, $refund);
        }

        if ($order) {
            return CurrencyService::convertOrderAmountToBase($order, (float) ($refund->amount ?? 0));
        }

        return (float) CurrencyService::convertToBase((float) ($refund->amount ?? 0), $refund->currency ?: 'USD')['base_amount'];
    }

    /**
     * Get order amount in base currency using the ADMIN's current exchange rate.
     * This represents the "fixed/expected" revenue at the reference rate,
     * NOT the rate the order actually settled at.
     * FX Gain/Loss = getOrderBaseAmount (actual) − getOrderFixedBaseAmount (fixed).
     */
    public static function getOrderFixedBaseAmount(Order $order): float
    {
        $currency = CurrencyService::normalizeCurrencyCode($order->currency ?: 'USD');
        $rate = CurrencyService::getRate($currency);

        return (float) $order->amount * $rate;
    }

    /**
     * Get refund amount in base currency using the ADMIN's current exchange rate.
     * Mirrors getOrderFixedBaseAmount logic for refund amounts.
     */
    public static function getRefundFixedBaseAmount(RefundRequest $refund): float
    {
        $order = $refund->order;
        $currency = CurrencyService::normalizeCurrencyCode(
            $order ? ($order->currency ?: 'USD') : ($refund->currency ?: 'USD')
        );
        $rate = CurrencyService::getRate($currency);

        return (float) $refund->amount * $rate;
    }

    public static function getStockUploadRate(InventoryVoucher $item): float
    {
        $purchaseCurrency = CurrencyService::normalizeCurrencyCode($item->local_currency ?: ($item->currency ?: 'USD'));
        $stockUploadRate = (float) ($item->currency_conversion_rate ?? 0);

        if ($stockUploadRate <= 0 || ($purchaseCurrency !== 'USD' && abs($stockUploadRate - 1.0) < 0.000001)) {
            $stockUploadRate = (float) CurrencyService::getExchangeRate($purchaseCurrency, CurrencyService::getBaseCurrency());
        }

        return $stockUploadRate;
    }

    public static function getInventoryUnitCostBase(InventoryVoucher $item): float
    {
        if ($item->purchase_value_per_unit_usd !== null) {
            return (float) $item->purchase_value_per_unit_usd;
        }

        return (float) $item->purchase_value_per_unit * self::getStockUploadRate($item);
    }

    public static function getInventoryPurchaseTotalBase(InventoryVoucher $item): float
    {
        if ($item->purchase_value_usd !== null) {
            return (float) $item->purchase_value_usd;
        }

        return (float) $item->purchase_value * self::getStockUploadRate($item);
    }

    /**
     * Total Cost of Goods Sold (COGS) = Opening Stock + Purchases - Closing Stock
     */
    public static function calculateCogs(float $openingStock, float $purchases, float $closingStock): float
    {
        return round($openingStock + $purchases - $closingStock, 2);
    }

    /**
     * Gross Profit = Revenue - Total COGS
     */
    public static function calculateGrossProfit(float $revenue, float $cogs): float
    {
        return round($revenue - $cogs, 2);
    }

    /**
     * Net Profit = Gross Profit - Voucher Lost Expense
     */
    public static function calculateNetProfit(float $grossProfit, float $lostExpense, float $fxGain = 0.0): float
    {
        return round($grossProfit - $lostExpense, 2);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\InventoryVoucher;
use App\Services\ProfitLossService;
use Tests\TestCase;

class ProfitLossServiceTest extends TestCase
{
    public function test_monetary_refund_reduces_revenue_without_reducing_inventory_cost(): void
    {
        $order = new Order([
            'order_id' => 'PUR-TEST-1',
            'voucher_id' => 'SKU-001',
            'quantity' => 10,
            'amount' => 100,
            'currency' => 'EUR',
            'exchange_rate_applied' => 1.25,
            'amount_in_base_currency' => 125,
            'status' => 'delivered',
        ]);

        $refund = new RefundRequest([
            'order_id' => $order->order_id,
            'amount' => 40,
            'currency' => 'EUR',
            'exchange_rate_applied' => 1.25,
            'amount_in_base_currency' => 50,
            'status' => 'approved',
        ]);
        $refund->setRelation('order', $order);

        $this->assertSame(10, ProfitLossService::getActiveSoldQuantity((int) $order->quantity));
        $this->assertSame(50.0, ProfitLossService::getRefundBaseAmount($refund));
        $expectedOrderFixed = 100 * \App\Services\CurrencyService::getRate('EUR');
        $expectedRefundFixed = 40 * \App\Services\CurrencyService::getRate('EUR');
        $this->assertSame($expectedOrderFixed, ProfitLossService::getOrderFixedBaseAmount($order));
        $this->assertSame($expectedRefundFixed, ProfitLossService::getRefundFixedBaseAmount($refund));
    }

    public function test_legacy_refund_uses_order_locked_rate_when_refund_snapshot_is_missing(): void
    {
        $order = new Order([
            'order_id' => 'PUR-TEST-2',
            'voucher_id' => 'SKU-002',
            'quantity' => 5,
            'amount' => 100,
            'currency' => 'EUR',
            'exchange_rate_applied' => 1.25,
            'amount_in_base_currency' => 125,
        ]);

        $refund = new RefundRequest([
            'order_id' => $order->order_id,
            'amount' => 40,
            'currency' => 'EUR',
            'amount_in_base_currency' => null,
            'status' => 'approved',
        ]);
        $refund->setRelation('order', $order);

        $this->assertSame(50.0, ProfitLossService::getRefundBaseAmount($refund));
    }

    public function test_inventory_uses_locked_usd_cost_snapshot(): void
    {
        $inventory = new InventoryVoucher([
            'currency' => 'EUR',
            'local_currency' => 'EUR',
            'currency_conversion_rate' => 1.25,
            'purchase_value' => 40,
            'purchase_value_per_unit' => 4,
            'purchase_value_usd' => 50,
            'purchase_value_per_unit_usd' => 5,
        ]);

        $this->assertSame(5.0, ProfitLossService::getInventoryUnitCostBase($inventory));
        $this->assertSame(50.0, ProfitLossService::getInventoryPurchaseTotalBase($inventory));
    }

    public function test_standardized_profit_and_loss_sample_formulas(): void
    {
        $revenue = 2170.58;
        $openingStock = 25.00;
        $purchases = 1789.20;
        $closingStock = 54.00;

        $cogs = ProfitLossService::calculateCogs($openingStock, $purchases, $closingStock);
        $this->assertSame(1760.20, $cogs);

        $grossProfit = ProfitLossService::calculateGrossProfit($revenue, $cogs);
        $this->assertSame(410.38, $grossProfit);

        $lostExpense = 0.00;
        $fxGain = 0.00;
        $netProfit = ProfitLossService::calculateNetProfit($grossProfit, $lostExpense, $fxGain);
        $this->assertSame(410.38, $netProfit);
    }
}

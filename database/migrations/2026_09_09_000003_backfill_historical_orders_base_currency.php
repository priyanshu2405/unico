<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Services\CurrencyService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Safely backfill historical orders where amount_in_base_currency is null
        try {
            DB::table('orders')
                ->whereNull('amount_in_base_currency')
                ->orderBy('id')
                ->chunkById(100, function ($orders) {
                    foreach ($orders as $order) {
                        // Find currency from associated inventory voucher if possible
                        $voucherCurrency = null;
                        if (!empty($order->voucher_id)) {
                            $voucherCurrency = DB::table('inventory_vouchers')
                                ->where('sku_id', $order->voucher_id)
                                ->value('currency');
                        }

                        $cleanCurrency = CurrencyService::normalizeCurrencyCode($order->currency ?? $voucherCurrency ?? 'USD');
                        $conversion = CurrencyService::convertToBase((float)$order->amount, $cleanCurrency);

                        DB::table('orders')->where('id', $order->id)->update([
                            'currency'                => $cleanCurrency,
                            'exchange_rate_applied'   => $conversion['rate'],
                            'amount_in_base_currency' => $conversion['base_amount'],
                        ]);
                    }
                });
        } catch (\Throwable $e) {
            // Log warning if migration table doesn't have orders yet during test setup
            \Illuminate\Support\Facades\Log::warning("Backfill historical orders base currency warning: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive reverse needed for backfill data
    }
};

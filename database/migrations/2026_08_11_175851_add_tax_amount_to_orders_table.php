<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('tax_amount', 15, 2)->default(0.00)->after('amount');
        });

        // Data Backfill for existing orders
        $orders = \Illuminate\Support\Facades\DB::table('orders')->get();
        foreach ($orders as $order) {
            if (empty($order->voucher_id)) continue;
            
            $voucher = \Illuminate\Support\Facades\DB::table('inventory_vouchers')->where('sku_id', $order->voucher_id)->first();
            if (!$voucher) continue;
            
            $unitTaxes = $voucher->purchased_qty > 0 ? (float) $voucher->taxes / $voucher->purchased_qty : 0;
            $taxAmount = $unitTaxes * $order->quantity;
            
            \Illuminate\Support\Facades\DB::table('orders')
                ->where('id', $order->id)
                ->update(['tax_amount' => $taxAmount]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('tax_amount');
        });
    }
};

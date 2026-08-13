<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\InventoryVoucher;

echo "Starting order tax_amount backfill...\n";

$orders = Order::all();
$updated = 0;

foreach ($orders as $order) {
    if (empty($order->voucher_id)) continue;

    $voucher = InventoryVoucher::where('sku_id', $order->voucher_id)->first();
    if (!$voucher) continue;

    $unitTaxes = $voucher->purchased_qty > 0 ? (float) $voucher->taxes / $voucher->purchased_qty : 0;
    $taxAmount = $unitTaxes * $order->quantity;

    $order->tax_amount = $taxAmount;
    $order->save();
    $updated++;
}

echo "Backfill complete. Updated {$updated} orders.\n";

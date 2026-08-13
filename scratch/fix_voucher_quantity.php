<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryVoucher;
use App\Models\Order;

$voucher = InventoryVoucher::where('sku_id', 'GLB-BC-IE-059')->first();
if ($voucher) {
    $allCodes = $voucher->upload_vouchers ?: [];
    $deliveredCodes = $voucher->delivered_vouchers ?: [];
    $remainingCodes = array_diff($allCodes, $deliveredCodes);

    $pendingOrdersQty = Order::where('voucher_id', $voucher->sku_id)
        ->whereIn('status', ['pending', 'completed'])
        ->sum('quantity');

    $correctQuantity = max(0, count($remainingCodes) - $pendingOrdersQty);
    
    $oldQty = $voucher->quantity;
    $voucher->quantity = $correctQuantity;
    $voucher->save();

    echo "SUCCESS: SKU GLB-BC-IE-059 quantity updated from {$oldQty} to {$correctQuantity}\n";
} else {
    echo "ERROR: Voucher GLB-BC-IE-059 not found\n";
}

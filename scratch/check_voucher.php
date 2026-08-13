<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$voucher = App\Models\InventoryVoucher::where('sku_id', 'GLB-BC-IE-059')->first();
if ($voucher) {
    echo "SKU: " . $voucher->sku_id . "\n";
    echo "Quantity: " . $voucher->quantity . "\n";
    echo "Status: " . $voucher->status . "\n";
    echo "Upload Vouchers Count: " . count($voucher->upload_vouchers ?? []) . "\n";
    echo "Delivered Vouchers Count: " . count($voucher->delivered_vouchers ?? []) . "\n";
    $allCodes = is_array($voucher->upload_vouchers) ? $voucher->upload_vouchers : [];
    $deliveredCodes = is_array($voucher->delivered_vouchers) ? $voucher->delivered_vouchers : [];
    echo "Diff Count: " . count(array_diff($allCodes, $deliveredCodes)) . "\n";
} else {
    echo "Voucher not found\n";
}

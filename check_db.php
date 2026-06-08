<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\InventoryVoucher;

$count = InventoryVoucher::count();
$latest = InventoryVoucher::orderBy('id', 'desc')->first();

echo "Total: $count\n";
if ($latest) {
    echo "Latest ID: " . $latest->id . "\n";
    echo "Latest SKU: " . $latest->sku_id . "\n";
} else {
    echo "No records found\n";
}

$all = InventoryVoucher::select('id', 'sku_id')->get();
foreach ($all as $v) {
    echo "ID: $v->id, SKU: $v->sku_id\n";
}

<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InventoryVoucher;

$vouchers = InventoryVoucher::all();
foreach ($vouchers as $v) {
    echo "SKU: " . $v->sku_id . "\n";
    echo "  Brand: " . $v->brand_name . "\n";
    echo "  Currency (Primary): " . $v->currency . "\n";
    echo "  Local Currency: " . $v->local_currency . "\n";
    echo "  Conversion Rate: " . $v->currency_conversion_rate . "\n";
    echo "  Student Sale Price: " . $v->student_sale_price . "\n";
    echo "  Agent Sale Price: " . $v->agent_sale_price . "\n";
    echo "  Purchase Value: " . $v->purchase_value . "\n";
    echo "  Purchase Value / Unit: " . $v->purchase_value_per_unit . "\n";
    echo "\n";
}

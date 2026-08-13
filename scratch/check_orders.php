<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;

$orders = Order::where('voucher_id', 'GLB-BC-IE-059')
    ->whereIn('status', ['pending', 'completed'])
    ->get();

echo "Pending/Completed Orders count: " . $orders->count() . "\n";
foreach ($orders as $order) {
    echo "Order ID: " . $order->order_id . " | Status: " . $order->status . " | Qty: " . $order->quantity . "\n";
}

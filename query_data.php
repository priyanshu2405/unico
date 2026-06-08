<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\User;
use App\Models\InventoryVoucher;

// Find Luffy
$user = User::where('name', 'like', '%Luffy%')
    ->orWhere('id', 'like', '%UNAL00171%')
    ->orWhere('email', 'like', '%Luffy%')
    ->first();

if (!$user) {
    // Try custom columns or list some users to find Luffy
    $user = User::where('username', 'like', '%Luffy%')->first();
}

if ($user) {
    echo "--- USER FOUND ---\n";
    echo "ID: " . $user->id . "\n";
    echo "Name: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Account Type: " . $user->account_type . "\n";
    echo "Country: " . var_export($user->country, true) . "\n";
    echo "State: " . var_export($user->state, true) . "\n";
    echo "Voucher Limit: " . $user->voucher_limit . "\n";
} else {
    echo "--- USER NOT FOUND ---\n";
    // Dump count and search options
    echo "Total users: " . User::count() . "\n";
    $someUsers = User::limit(5)->get(['id', 'name', 'email', 'account_type', 'country']);
    foreach ($someUsers as $su) {
        echo " - ID: {$su->id}, Name: {$su->name}, Account Type: {$su->account_type}, Country: {$su->country}\n";
    }
}

echo "\n--- VOUCHERS IN DATABASE ---\n";
$vouchers = InventoryVoucher::get();
echo "Total vouchers in DB: " . $vouchers->count() . "\n";
foreach ($vouchers as $v) {
    echo "ID: {$v->id}, SKU: {$v->sku_id}, Brand: {$v->brand_name}, Country: " . json_encode($v->country_region) . ", Expired: " . ($v->is_expired ? 'Yes' : 'No') . ", State: " . json_encode($v->state) . "\n";
}

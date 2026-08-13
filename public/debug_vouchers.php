<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');

echo "=== DIAGNOSTIC START ===\n\n";

// 1. Get the current user or user 'Atiqa'
$user = App\Models\User::where('name', 'like', '%Atiqa%')->first();
if ($user) {
    echo "Student User found:\n";
    echo "ID: " . $user->id . "\n";
    echo "Name: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Account Type: " . $user->account_type . "\n";
    echo "Country: " . ($user->country ?? 'NULL') . "\n";
    echo "State: " . ($user->state ?? 'NULL') . "\n";
    echo "Voucher Limit: " . $user->voucher_limit . "\n";
    echo "----------------------------------------\n\n";
} else {
    echo "Student User 'Atiqa' not found!\n";
    echo "----------------------------------------\n\n";
}

// 2. Check System settings
$stopAllSales = \App\Models\SystemSetting::where('settings_key', 'stop_all_sales')->value('settings_value');
$stoppedBrandsJson = \App\Models\SystemSetting::where('settings_key', 'stopped_brands')->value('settings_value');
$stoppedCountriesJson = \App\Models\SystemSetting::where('settings_key', 'stopped_countries')->value('settings_value');
$emergencyFreeze = \App\Models\SystemSetting::where('settings_key', 'emergency_freeze')->value('settings_value');

echo "System Settings:\n";
echo "Stop All Sales: " . ($stopAllSales ?? 'NULL') . "\n";
echo "Stopped Brands: " . ($stoppedBrandsJson ?? '[]') . "\n";
echo "Stopped Countries: " . ($stoppedCountriesJson ?? '[]') . "\n";
echo "Emergency Freeze: " . ($emergencyFreeze ?? 'NULL') . "\n";
echo "----------------------------------------\n\n";

// 3. Get all vouchers
$vouchers = App\Models\InventoryVoucher::all();
echo "Total Vouchers in DB: " . $vouchers->count() . "\n\n";

if ($vouchers->count() > 0) {
    echo "Voucher List:\n";
    foreach ($vouchers as $v) {
        echo "ID: " . $v->id . "\n";
        echo "SKU ID: " . $v->sku_id . "\n";
        echo "Brand Name: " . $v->brand_name . "\n";
        echo "Quantity: " . $v->quantity . "\n";
        echo "Status: " . $v->status . "\n";
        echo "Is Expired: " . ($v->is_expired ? 'Yes' : 'No') . "\n";
        echo "Country Region (Raw): " . json_encode($v->country_region) . "\n";
        echo "State (Raw): " . json_encode($v->state) . "\n";
        echo "---------------------\n";
    }
} else {
    echo "No vouchers exist in the inventory_vouchers table!\n";
}

echo "\n=== DIAGNOSTIC END ===\n";

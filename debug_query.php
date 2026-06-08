<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\User;
use App\Models\InventoryVoucher;
use Illuminate\Support\Facades\DB;

$user = User::where('name', 'Luffy')->first();

if (!$user) {
    echo "Luffy not found\n";
    exit;
}

echo "User details: Country = '{$user->country}', State = '{$user->state}', Account Type = '{$user->account_type}'\n\n";

DB::enableQueryLog();

$query = InventoryVoucher::where('is_expired', false);

if (!in_array($user->account_type, ['admin', 'manager'])) {
    $query->where(function ($q) use ($user) {
        $q->whereJsonContains('country_region', $user->country)
            ->orWhereJsonContains('country_region', 'all')
            ->orWhereJsonContains('country_region', 'GLB')
            ->orWhere('country_region', 'like', '%all%')
            ->orWhere('country_region', 'like', '%GLB%');
    });

    $query->where(function ($q) use ($user) {
        $q->whereNull('state')
            ->orWhere('state', '[]')
            ->orWhere('state', 'null')
            ->orWhereJsonContains('state', $user->state)
            ->orWhereJsonContains('state', 'all');
    });
}

$vouchers = $query->latest()->get();

$log = DB::getQueryLog();
echo "Generated SQL Query:\n";
print_r($log);

echo "\nResulting Vouchers Count: " . $vouchers->count() . "\n";
foreach ($vouchers as $v) {
    echo " - ID: {$v->id}, SKU: {$v->sku_id}, Brand: {$v->brand_name}, Country: " . json_encode($v->country_region) . ", State: " . json_encode($v->state) . "\n";
}

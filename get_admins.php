<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\User;

$admins = User::where('account_type', 'admin')->get();

echo "--- ADMIN USERS ---\n";
foreach ($admins as $admin) {
    echo "ID: {$admin->id}\n";
    echo "User ID: {$admin->user_id}\n";
    echo "Name: {$admin->name}\n";
    echo "Email: {$admin->email}\n";
    echo "Created At: {$admin->created_at}\n";
    echo "-------------------\n";
}

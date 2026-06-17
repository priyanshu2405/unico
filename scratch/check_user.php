<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::where('email', 'support1@yopmail.com')->first();
if ($user) {
    echo "User found!\n";
    echo "Name: " . $user->name . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Password Hash: " . $user->password . "\n";
    echo "Account Type: " . $user->account_type . "\n";
} else {
    echo "User support1@yopmail.com not found in the database.\n";
}

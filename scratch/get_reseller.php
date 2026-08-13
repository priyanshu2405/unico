<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$user = User::where('account_type', 'reseller_agent')->first();

if (!$user) {
    $user = User::create([
        'name' => 'Test Reseller',
        'email' => 'reseller@example.com',
        'password' => Hash::make('password'),
        'account_type' => 'reseller_agent',
        'user_id' => User::generateNextUserId('reseller_agent'),
        'email_verified_at' => now(),
        'is_active' => true, // guessing based on standard auth
    ]);
    echo "Created new reseller.\n";
} else {
    $user->password = Hash::make('password');
    $user->save();
    echo "Updated existing reseller.\n";
}

echo "Email: " . $user->email . "\n";
echo "Password: password\n";

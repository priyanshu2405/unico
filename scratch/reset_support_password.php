<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = 'support1@yopmail.com';
$newPassword = 'Password123!';

$user = User::where('email', $email)->first();
if ($user) {
    $user->password = Hash::make($newPassword);
    $user->save();
    echo "Successfully updated password for $email to '$newPassword'\n";
} else {
    echo "User not found.\n";
}

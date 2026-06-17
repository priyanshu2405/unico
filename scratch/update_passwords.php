<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$emails = ['hassanzoya085@gmail.com', 'daisycollins6667@gmail.com'];
$newPassword = 'Password123!';
$hashed = Hash::make($newPassword);

foreach ($emails as $email) {
    $user = User::where('email', $email)->first();
    if ($user) {
        $user->password = $hashed;
        $user->save();
        echo "Successfully updated password for: $email to '$newPassword'\n";
    } else {
        echo "User $email not found.\n";
    }
}

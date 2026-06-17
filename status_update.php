<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = \App\Models\User::find(419);
if ($u) {
    $u->profile_verification_status = 'verified';
    $u->save();
    echo "Status updated to verified successfully";
} else {
    echo "User not found";
}

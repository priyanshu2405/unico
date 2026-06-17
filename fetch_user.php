<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = \App\Models\User::find(419);
if ($u) {
    echo "ID: {$u->id}\n";
    echo "Profile Verification Status: {$u->profile_verification_status}\n";
    echo "Is Active: " . ($u->is_active ? 'true' : 'false') . "\n";
} else {
    echo "User not found";
}
?>

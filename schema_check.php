<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$result = \DB::select("SHOW COLUMNS FROM users LIKE 'profile_verification_status'");
print_r($result);

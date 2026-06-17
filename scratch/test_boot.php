<?php
echo "Starting test...\n";
require __DIR__ . '/../vendor/autoload.php';
echo "Autoload required.\n";
$app = require_once __DIR__ . '/../bootstrap/app.php';
echo "App bootstrap required.\n";

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo "Kernel bootstrapped successfully!\n";

use Illuminate\Support\Facades\Artisan;
echo "Running Artisan command...\n";
$status = Artisan::call('list');
echo "Artisan list status: $status\n";

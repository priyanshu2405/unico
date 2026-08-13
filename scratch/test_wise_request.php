<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "App Env: " . config('app.env') . "\n";

try {
    // 1. Without verify => false
    echo "Request 1 (Normal): ";
    $res = Http::post('https://api.sandbox.transferwise.tech/v3/quotes');
    echo "Success! Status: " . $res->status() . "\n";
} catch (\Throwable $e) {
    echo "Failed (as expected): " . $e->getMessage() . "\n";
}

try {
    // 2. Setting verify => false
    echo "Request 2 (with Http::globalOptions verify => false): ";
    Http::globalOptions(['verify' => false]);
    $res = Http::post('https://api.sandbox.transferwise.tech/v3/quotes');
    echo "Success! Status: " . $res->status() . "\n";
} catch (\Throwable $e) {
    echo "Failed: " . $e->getMessage() . "\n";
}

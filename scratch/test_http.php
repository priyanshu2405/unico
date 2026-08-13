<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

try {
    Http::globalOptions(['verify' => false]);
    echo "SUCCESS: Http::globalOptions is supported!\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

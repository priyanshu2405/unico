<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$result = \DB::select("SELECT name, email, account_type FROM users WHERE account_type = 'admin'");
foreach ($result as $row) {
    echo "Name: " . $row->name . " | Email: " . $row->email . " | Type: " . $row->account_type . "\n";
}

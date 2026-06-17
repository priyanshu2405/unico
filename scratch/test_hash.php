<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;

$hash = '$2y$12$hY6k0ds5Zd92G57czQaNEeHXzXmF38flwBkFtEXxOMfoERfUEJchq';
$candidates = [
    'password',
    'secret',
    'Omsairam@1234',
    'testing@123',
    'admin123',
    'support123',
    'support1@123',
    'support@123',
    '12345678',
    '123456'
];

foreach ($candidates as $candidate) {
    if (Hash::check($candidate, $hash)) {
        echo "Match found: '$candidate'\n";
        exit;
    }
}
echo "No matches found from the candidate list.\n";

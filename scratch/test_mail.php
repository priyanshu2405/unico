<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;

echo "Mailer: " . config('mail.default') . "\n";
echo "Host: " . config('mail.mailers.smtp.host') . "\n";

try {
    echo "Sending test mail...\n";
    Mail::raw('Test email content', function ($message) {
        $message->to('test@example.com')->subject('Local Test Mail');
    });
    echo "SUCCESS: Mail sent!\n";
} catch (\Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}

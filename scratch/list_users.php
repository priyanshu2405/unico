<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = App\Models\User::where('account_type', 'admin')->first();
$student = App\Models\User::where('account_type', 'student')->first();

if ($admin) {
    echo "ADMIN: Email: " . $admin->email . " | Name: " . $admin->name . "\n";
} else {
    echo "No admin found\n";
}

if ($student) {
    echo "STUDENT: Email: " . $student->email . " | Name: " . $student->name . "\n";
} else {
    echo "No student found\n";
}

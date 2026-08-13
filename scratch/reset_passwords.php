<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$admin = User::where('email', 'admin@unicou.nl')->first();
if ($admin) {
    $admin->password = Hash::make('admin12345');
    $admin->save();
    echo "SUCCESS: Admin (admin@unicou.nl) password set to: admin12345\n";
}

$student = User::where('email', 'soni@yopmail.com')->first();
if ($student) {
    $student->password = Hash::make('student12345');
    $student->save();
    echo "SUCCESS: Student (soni@yopmail.com) password set to: student12345\n";
}

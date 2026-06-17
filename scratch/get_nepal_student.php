<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$users = User::where('account_type', 'student')
    ->where(function($q) {
        $q->where('country', 'like', '%Nepal%')
          ->orWhere('country', 'NP')
          ->orWhere('country', 'NPL')
          ->orWhere('country_iso', 'NP')
          ->orWhere('country_iso', 'NPL');
    })
    ->get(['id', 'name', 'email', 'country', 'country_iso', 'account_type']);

if ($users->isEmpty()) {
    echo "No students from Nepal found. Here are some other students:\n";
    $otherStudents = User::where('account_type', 'student')->limit(5)->get(['id', 'name', 'email', 'country', 'country_iso']);
    foreach ($otherStudents as $student) {
        echo "ID: {$student->id}, Name: {$student->name}, Email: {$student->email}, Country: {$student->country} ({$student->country_iso})\n";
    }
} else {
    foreach ($users as $student) {
        echo "ID: {$student->id}, Name: {$student->name}, Email: {$student->email}, Country: {$student->country} ({$student->country_iso})\n";
    }
}

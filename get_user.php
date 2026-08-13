<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('name', 'like', '%Atiqa%')->first();
if ($user) {
    print_r($user->toArray());
} else {
    echo "User Atiqa not found\n";
}

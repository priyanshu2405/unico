<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Override configuration for local database connection
config(['database.connections.mysql.database' => 'unicou']);
config(['database.connections.mysql.username' => 'root']);
config(['database.connections.mysql.password' => 'root']);

// Reconnect to apply the database credentials
\DB::purge('mysql');
\DB::reconnect('mysql');

use App\Models\User;
use App\Helpers\CountryHelper;

$users = User::whereNotNull('country')
    ->where('country', '!=', '')
    ->get();

$countries = CountryHelper::getAllCountries();
$updatedCount = 0;

foreach ($users as $user) {
    $iso = null;
    foreach ($countries as $code => $name) {
        if (strcasecmp(trim($user->country), trim($name)) === 0) {
            $iso = $code;
            break;
        }
    }
    
    // Additional manual aliases if needed
    if (!$iso) {
        $countryLower = strtolower(trim($user->country));
        if ($countryLower === 'iran') {
            $iso = 'IR';
        } elseif ($countryLower === 'russia') {
            $iso = 'RU';
        } elseif ($countryLower === 'netherland' || $countryLower === 'netherlands') {
            $iso = 'NL';
        } elseif ($countryLower === 'sauth africa') {
            $iso = 'ZA';
        }
    }

    if ($iso) {
        if ($user->country_iso !== $iso) {
            $oldIso = $user->country_iso ?? 'NULL';
            $user->country_iso = $iso;
            $user->save();
            echo "Updated User ID {$user->id} ({$user->name}): country='{$user->country}' | country_iso: '{$oldIso}' => '{$iso}'\n";
            $updatedCount++;
        }
    } else {
        echo "Warning: Could not resolve ISO for country '{$user->country}' (User ID {$user->id})\n";
    }
}

echo "Successfully updated {$updatedCount} users.\n";

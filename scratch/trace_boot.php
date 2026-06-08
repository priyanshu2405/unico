<?php
define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

echo "1. Autoloader loaded.\n";

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

echo "2. App bootstrapped.\n";

$app->booting(function () {
    echo "3. App booting started...\n";
});

$app->booted(function () {
    echo "4. App booted.\n";
});

// Let's manually boot the application container
echo "5. Resolving kernel...\n";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "6. Bootstrapping console kernel...\n";
// The console kernel boots the providers during bootstrap()
$kernel->bootstrap();

echo "7. Running command...\n";
$status = $kernel->handle(
    $input = new Symfony\Component\Console\Input\ArgvInput(['artisan', '--version']),
    new Symfony\Component\Console\Output\ConsoleOutput
);

echo "8. Done! Status: $status\n";

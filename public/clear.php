<?php
// Standalone script to delete cached config and route files
$configCache = __DIR__ . '/../bootstrap/cache/config.php';
$routeCache = __DIR__ . '/../bootstrap/cache/routes-v7.php';
$packagesCache = __DIR__ . '/../bootstrap/cache/packages.php';
$servicesCache = __DIR__ . '/../bootstrap/cache/services.php';

if (file_exists($configCache)) {
    if (unlink($configCache)) {
        echo "Successfully deleted config cache.<br>";
    } else {
        echo "Failed to delete config cache.<br>";
    }
} else {
    echo "Config cache was already cleared.<br>";
}

if (file_exists($routeCache)) {
    if (unlink($routeCache)) {
        echo "Successfully deleted route cache.<br>";
    } else {
        echo "Failed to delete route cache.<br>";
    }
} else {
    echo "Route cache was already cleared.<br>";
}

if (file_exists($packagesCache)) {
    unlink($packagesCache);
}
if (file_exists($servicesCache)) {
    unlink($servicesCache);
}

echo "Cleanup complete! Please try reloading your page now.";

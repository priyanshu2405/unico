<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1", 'root', '');
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Databases:\n";
    foreach ($databases as $t) {
        echo "- $t\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=unicou", 'root', 'root');
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in users table:\n";
    foreach ($columns as $c) {
        echo "- {$c['Field']} ({$c['Type']})\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

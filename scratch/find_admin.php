<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=janshaur_unico_system", 'root', '');
    $newHash = password_hash('admin12345', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@unicou.nl'");
    $stmt->execute([$newHash]);
    echo "SUCCESS: Admin password has been reset to admin12345\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

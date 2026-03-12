<?php
/**
 * One-time script to insert a new admin.
 * Run once via browser or CLI, then delete this file for security.
 */
if (PHP_SAPI === 'cli') {
    $_SERVER['SERVER_NAME'] = 'localhost';
}
require_once __DIR__ . '/../includes/db.php';

$username = 'admin';
$email    = 'admin@rupkotha.local';
$password = 'Admin@123'; // Change after first login

$hashed = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hashed]);
    echo "Admin created successfully.\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
    echo "\nDelete this file (admin/insert-admin.php) after use.\n";
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo "An admin with that email already exists. Use those credentials or change \$email in this script.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

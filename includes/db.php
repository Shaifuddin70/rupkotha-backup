<?php
// db.php - Database configuration file

// Check if the current server is localhost. You can use a specific server name or IP address.
if ($_SERVER['SERVER_NAME'] === 'localhost') {
    // Localhost database credentials
    $host = 'localhost';
    $db = 'rpecommerce';
    $user = 'root';
    $pass = 'root';
} else {
    // Hosting database credentials
    $host = 'localhost'; // Often 'localhost' or a specific host IP/name
    $db = 'rokiblo3_rpecommerce';
    $user = 'rokiblo3_rpecommerce';
    $pass = 'Se7wypbb4ztpvwwTgrWU';
}

$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    // Create the PDO instance
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Terminate script and show an error if connection fails
    die("Database connection failed: " . $e->getMessage());
}

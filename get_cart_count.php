<?php
// Get Cart Count - AJAX endpoint for updating cart badge
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get cart items count
$cart_count = count($_SESSION['cart'] ?? []);

echo json_encode([
    'success' => true,
    'count' => $cart_count
]);
?>

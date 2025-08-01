<?php
// This file handles adding items to the cart. It doesn't produce any visible output.

// Start the session to access the cart.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once 'includes/db.php';
require_once 'includes/functions.php';

// --- Get Product ID and Quantity ---
// Handles both GET requests (from product cards) and POST requests (from the product detail page).
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?: 1; // Default to 1 if not specified

// --- Validation ---
if (!$product_id || $quantity <= 0) {
    // Set an error message in the session
    $_SESSION['flash_message'] = [
        'type' => 'danger',
        'message' => 'Invalid product or quantity specified.'
    ];
    // Redirect back to the previous page or homepage
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

// --- Check Product Availability ---
try {
    $stmt = $pdo->prepare("SELECT name, stock FROM products WHERE id = :id");
    $stmt->execute([':id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Product not found.'];
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
    }

    $stock = (int)$product['stock'];
    $product_name = $product['name'];

    // Check if the product is out of stock
    if ($stock <= 0) {
        $_SESSION['flash_message'] = ['type' => 'warning', 'message' => "Sorry, '" . esc_html($product_name) . "' is currently out of stock."];
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
    }

    // --- Add to Cart Logic ---
    // Initialize the cart if it doesn't exist
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $current_quantity_in_cart = $_SESSION['cart'][$product_id] ?? 0;
    $new_quantity = $current_quantity_in_cart + $quantity;

    // Check if the desired quantity exceeds available stock
    if ($new_quantity > $stock) {
        // Only add the remaining stock to the cart
        $_SESSION['cart'][$product_id] = $stock;
        $message = "Only {$stock} units of '" . esc_html($product_name) . "' are available. Your cart has been updated with the maximum quantity.";
        $_SESSION['flash_message'] = ['type' => 'warning', 'message' => $message];
    } else {
        // Add the item and quantity to the session cart
        $_SESSION['cart'][$product_id] = $new_quantity;
        $message = "Added {$quantity} x '" . esc_html($product_name) . "' to your cart.";
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => $message];
    }

} catch (PDOException $e) {
    // Handle database errors gracefully
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'A database error occurred. Please try again.'];
    // In a production environment, you would log this error instead of showing details.
    // error_log($e->getMessage());
}

// --- Redirect ---
// Send the user back to the page they came from.
redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');

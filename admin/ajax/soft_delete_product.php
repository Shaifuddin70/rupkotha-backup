<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $product_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($product_id) {
        try {
            // When deleting, also set the product to inactive for consistency
            $stmt = $pdo->prepare("UPDATE products SET deleted_at = NOW(), is_active = 0 WHERE id = ?");
            if ($stmt->execute([$product_id])) {
                echo json_encode(['status' => 'success', 'message' => 'Product deleted and deactivated.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete product.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product ID.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>

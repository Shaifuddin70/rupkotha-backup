<?php
// ajax/delete-product.php

// Use __DIR__ to build reliable paths that will not fail.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// We'll return JSON responses
header('Content-Type: application/json');

// Ensure an admin is performing this action
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (isset($_POST['id'])) {
    $product_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if (!$product_id) {
        $response['message'] = 'Invalid Product ID.';
        echo json_encode($response);
        exit;
    }

    try {
        // --- UPDATED SAFETY CHECK ---
        // Check if the product exists in any orders that are NOT marked as 'Completed'.
        $check_stmt = $pdo->prepare(
            "SELECT COUNT(o.id) 
             FROM order_items oi
             JOIN orders o ON oi.order_id = o.id
             WHERE oi.product_id = ? AND o.status NOT IN ('Completed', 'Cancelled')"
        );
        $check_stmt->execute([$product_id]);
        $active_order_count = $check_stmt->fetchColumn();

        if ($active_order_count > 0) {
            // If the product is in active or pending orders, do not delete it.
            $response['message'] = 'This product cannot be deleted because it is part of active or pending orders. Please complete or cancel those orders first, or set the product status to "Inactive".';
            echo json_encode($response);
            exit;
        }

        // --- DELETION PROCESS (only if product is not in any active orders) ---

        $pdo->beginTransaction();

        // 1. Get the image filename before deleting the product record
        $img_stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $img_stmt->execute([$product_id]);
        $image_to_delete = $img_stmt->fetchColumn();

        // 2. Delete from hero_products (ON DELETE CASCADE should handle this, but it's safe to be explicit)
        $hero_stmt = $pdo->prepare("DELETE FROM hero_products WHERE product_id = ?");
        $hero_stmt->execute([$product_id]);

        // 3. Since we are allowing deletion even if the product is in 'Completed' orders,
        // we should NOT delete the order_items records. This preserves history.
        // Instead, we can set the product_id in order_items to NULL if the foreign key allows it.
        // For now, we will leave the order_items as they are to maintain historical data.
        // If you had a foreign key constraint ON DELETE SET NULL, this would be automatic.

        // 4. Finally, delete the product itself
        $product_stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $product_stmt->execute([$product_id]);

        $pdo->commit();

        // 5. After successful DB deletion, delete the image file from the server
        if ($image_to_delete) {
            $file_path = __DIR__ . '/../assets/uploads/' . $image_to_delete;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        $response = ['status' => 'success', 'message' => 'Product has been successfully deleted. It will remain in completed order histories.'];

    } catch (PDOException $e) {
        $pdo->rollBack();
        $response['message'] = 'A database error occurred during deletion.';
        // For debugging: $response['error'] = $e->getMessage();
    }
} else {
    $response['message'] = 'No Product ID provided.';
}

echo json_encode($response);

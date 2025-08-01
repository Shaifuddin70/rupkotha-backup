<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['product_id'], $_POST['image_path'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$new_main_image_path = trim($_POST['image_path']);

if (!$product_id || empty($new_main_image_path)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
    exit;
}

$pdo->beginTransaction();
try {
    // 1. Get the current main image from the products table
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $old_main_image_path = $stmt->fetchColumn();

    // 2. Find the additional image record that matches the new main image path
    $stmt = $pdo->prepare("SELECT id FROM product_images WHERE product_id = ? AND image_path = ?");
    $stmt->execute([$product_id, $new_main_image_path]);
    $additional_image_id = $stmt->fetchColumn();

    if (!$additional_image_id) {
        throw new Exception("The selected image does not exist in the additional images table.");
    }

    // 3. Update the products table with the new main image
    $stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
    $stmt->execute([$new_main_image_path, $product_id]);

    // 4. Update the old additional image record with the old main image path
    $stmt = $pdo->prepare("UPDATE product_images SET image_path = ? WHERE id = ?");
    $stmt->execute([$old_main_image_path, $additional_image_id]);

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Main image updated successfully.']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}

<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit;
}

$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Product ID.']);
    exit;
}

try {
    // First, get the main product image
    $main_stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $main_stmt->execute([$product_id]);
    $main_image_path = $main_stmt->fetchColumn();

    if (!$main_image_path) {
        echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
        exit;
    }

    // Prepare the main image for the gallery array
    $images = [[
        'id' => 'main',
        'path' => $main_image_path,
        'is_main' => true
    ]];

    // Then, get all additional images
    $additional_stmt = $pdo->prepare("SELECT id, image_path FROM product_images WHERE product_id = ? ORDER BY id");
    $additional_stmt->execute([$product_id]);
    $additional_images = $additional_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($additional_images as $img) {
        $images[] = [
            'id' => $img['id'],
            'path' => $img['image_path'],
            'is_main' => false
        ];
    }

    echo json_encode(['status' => 'success', 'images' => $images]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

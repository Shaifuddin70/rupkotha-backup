<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id']) && isset($_FILES['image'])) {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $image_file = $_FILES['image'];

    if ($product_id && $image_file['error'] === UPLOAD_ERR_OK) {
        try {
            $image_name = handleImageUpload($image_file);
            if ($image_name) {
                $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
                if ($stmt->execute([$product_id, $image_name])) {
                    echo json_encode(['status' => 'success', 'message' => 'Image uploaded successfully.']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to save image to database.']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to upload image.']);
            }
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid product ID or no image uploaded.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}

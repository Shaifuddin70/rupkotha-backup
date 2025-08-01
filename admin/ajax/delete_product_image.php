<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['image_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

$image_id = filter_input(INPUT_POST, 'image_id', FILTER_VALIDATE_INT);

if (!$image_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Image ID.']);
    exit;
}

try {
    // First, get the image path so we can delete the file from the server
    $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ?");
    $stmt->execute([$image_id]);
    $image_path = $stmt->fetchColumn();

    if ($image_path) {
        // Delete the database record
        $delete_stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
        $delete_stmt->execute([$image_id]);

        // Delete the actual file from the server
        $file_to_delete = __DIR__ . '/../assets/uploads/' . $image_path;
        if (file_exists($file_to_delete)) {
            unlink($file_to_delete);
        }

        echo json_encode(['status' => 'success', 'message' => 'Image deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Image not found.']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

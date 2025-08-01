<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit;
}

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        try {
            // Safety Check: See if any products are using this category
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                $response['message'] = 'Cannot delete category because it has products associated with it. Please reassign products to another category first.';
            } else {
                $delete_stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                if ($delete_stmt->execute([$id])) {
                    $response = ['status' => 'success', 'message' => 'Category deleted successfully!'];
                } else {
                    $response['message'] = 'Failed to delete category from the database.';
                }
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
}

echo json_encode($response);

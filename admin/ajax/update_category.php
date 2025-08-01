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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['name'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);

    if ($id && !empty($name)) {
        try {
            // Check if another category with the same name already exists
            $check_stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
            $check_stmt->execute([$name, $id]);
            if ($check_stmt->fetch()) {
                $response['message'] = 'Another category with this name already exists.';
            } else {
                $update_stmt = $pdo->prepare("UPDATE categories SET name = ?, updated_at = NOW() WHERE id = ?");
                if ($update_stmt->execute([$name, $id])) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Category updated successfully!',
                        'id' => $id,
                        'name' => $name,
                        'updated_at' => format_date(date("Y-m-d H:i:s")) // Get current formatted date
                    ];
                } else {
                    $response['message'] = 'Failed to update category in the database.';
                }
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Category name cannot be empty.';
    }
}

echo json_encode($response);

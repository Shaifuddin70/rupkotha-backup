<?php
// admin/ajax/toggle_product_status.php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if (isset($_POST['id'], $_POST['status'])) {
    $product_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);

    if ($product_id && ($status === 0 || $status === 1)) {
        try {
            $stmt = $pdo->prepare("UPDATE products SET is_active = ? WHERE id = ?");
            if ($stmt->execute([$status, $product_id])) {
                $response = ['status' => 'success'];
            } else {
                $response['message'] = 'Failed to update status in database.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
}

echo json_encode($response);

<?php
// admin/ajax/toggle_hero_status.php
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$response = ['status' => 'error', 'message' => 'Invalid request.'];

if (isset($_POST['id'], $_POST['status'])) {
    $item_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);

    // Validate that status is either 0 or 1
    if ($item_id && ($status === 0 || $status === 1)) {
        try {
            $stmt = $pdo->prepare("UPDATE hero_products SET is_active = ? WHERE id = ?");
            if ($stmt->execute([$status, $item_id])) {
                $response = ['status' => 'success'];
            } else {
                $response['message'] = 'Failed to update status in the database.';
            }
        } catch (PDOException $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    }
}

echo json_encode($response);

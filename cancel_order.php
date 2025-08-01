<?php
// cancel_order.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// 1. Authentication and Validation
if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Authentication required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

$order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Order ID.']);
    exit;
}

// 2. Begin Transaction
$pdo->beginTransaction();

try {
    // 3. Verify the order belongs to the user and is 'Pending'
    $order_stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'Pending'");
    $order_stmt->execute([$order_id, $user_id]);
    $order = $order_stmt->fetch();

    if (!$order) {
        throw new Exception("Order not found or cannot be cancelled.");
    }

    // 4. Get all items from the order
    $items_stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $items_stmt->execute([$order_id]);
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Loop through items and restock them
    foreach ($order_items as $item) {
        if ($item['product_id']) {
            $update_stock_stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $update_stock_stmt->execute([$item['quantity'], $item['product_id']]);
        }
    }

    // 6. Update the order status to 'Cancelled'
    $cancel_stmt = $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ?");
    $cancel_stmt->execute([$order_id]);

    // 7. Commit Transaction
    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Order has been cancelled successfully.']);

} catch (Exception $e) {
    // 8. Rollback on error
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}

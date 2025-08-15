<?php
// admin/orders.php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Ensure admin is logged in
if (!isAdmin()) {
    redirect('login.php');
}

// --- Handle Order Status Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $status = trim($_POST['status']);
    $allowed_statuses = ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'];

    if ($order_id && in_array($status, $allowed_statuses)) {

        $pdo->beginTransaction();
        try {
            // If the order is being cancelled, restock the items
            if ($status === 'Cancelled') {
                // Get all items from the order
                $items_stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $items_stmt->execute([$order_id]);
                $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

                // Loop through items and update stock
                foreach ($order_items as $item) {
                    if ($item['product_id']) { // Ensure product exists before updating stock
                        $update_stock_stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                        $update_stock_stmt->execute([$item['quantity'], $item['product_id']]);
                    }
                }
            }

            // Update the order status and updated_at timestamp
            $update_order_stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            $update_order_stmt->execute([$status, $order_id]);

            // If all queries were successful, commit the transaction
            $pdo->commit();
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Order #{$order_id} status updated successfully."];
        } catch (Exception $e) {
            // If any part of the process fails, roll back the transaction
            $pdo->rollBack();
            // You might want to log the actual error for debugging: error_log($e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Failed to update order status due to a database error.'];
        }
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid order ID or status.'];
    }

    // Redirect back to the orders page, preserving any filters
    $query_string = http_build_query($_GET);
    redirect('orders.php?' . $query_string);
}

// --- Filtering & Pagination Logic ---
$statuses = ['All', 'Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'];
$current_status = trim($_GET['status'] ?? 'All');
if (!in_array($current_status, $statuses)) {
    $current_status = 'All';
}

$search_id = trim($_GET['search_id'] ?? '');

// Get counts for each tab
$counts_query = "SELECT status, COUNT(id) as count FROM orders GROUP BY status";
$counts_stmt = $pdo->query($counts_query);
$status_counts = $counts_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$total_all_orders = array_sum($status_counts);

$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build the WHERE clause
$where_clauses = [];
$params = [];

if (!empty($search_id)) {
    $where_clauses[] = "o.id = ?";
    $params[] = $search_id;
}
if ($current_status !== 'All') {
    $where_clauses[] = "o.status = ?";
    $params[] = $current_status;
}

$where_sql = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Get total count for pagination
$total_orders_stmt = $pdo->prepare("SELECT COUNT(o.id) FROM orders o" . $where_sql);
$total_orders_stmt->execute($params);
$total_orders = $total_orders_stmt->fetchColumn();
$total_pages = ceil($total_orders / $per_page);

// Fetch the orders for the current page
$sql = "SELECT o.*, u.username 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id"
    . $where_sql .
    " ORDER BY o.created_at DESC 
        LIMIT {$per_page} OFFSET {$offset}";

$orders_stmt = $pdo->prepare($sql);
$orders_stmt->execute($params);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> flash-message alert-dismissible fade show" role="alert">
        <strong><?= $_SESSION['flash_message']['type'] === 'success' ? 'Success!' : 'Error!' ?></strong>
        <?= $_SESSION['flash_message']['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<div class="filter-tabs mb-3">
    <?php foreach ($statuses as $status): ?>
        <a class="filter-tab <?= $current_status === $status ? 'active' : '' ?>" href="?status=<?= $status ?>">
            <?= $status ?>
            <span class="badge badge-modern ms-1">
                <?= $status === 'All' ? $total_all_orders : ($status_counts[$status] ?? 0) ?>
            </span>
        </a>
    <?php endforeach; ?>
</div>

<div class="main-card">
    <div class="card-header-modern d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h3 class="card-title-modern mb-0">
            <?= $current_status === 'All' ? 'All Orders' : $current_status . ' Orders' ?>
        </h3>
        <form method="get" class="search-container d-flex align-items-center">
            <input type="hidden" name="status" value="<?= esc_html($current_status) ?>">
            <input type="text" id="orderSearchInput" name="search_id" class="form-control search-input" placeholder="Search by Order ID..."
                value="<?= esc_html($search_id) ?>">
            <i class="bi bi-search search-icon"></i>
        </form>
    </div>
    <div class="table-responsive">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="bi bi-receipt"></i>
                </div>
                <h4>No Orders Found</h4>
                <p class="text-muted">There are no orders matching the current filter.</p>
            </div>
        <?php else: ?>
            <table id="ordersTable" class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="ordersTableBody">
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td data-label="Order ID" class="fw-bold">#<?= esc_html($order['id']) ?></td>
                            <td data-label="Customer"><?= esc_html($order['username'] ?? 'Guest') ?></td>
                            <td data-label="Date"><?= format_date($order['created_at']) ?></td>
                            <td data-label="Total"><?= formatPrice($order['total_amount']) ?></td>
                            <td data-label="Payment"><span class="badge badge-modern badge-category"><?= esc_html($order['payment_method']) ?></span></td>
                            <td data-label="Status">
                                <form method="post" class="d-flex gap-2 status-form">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <input type="hidden" name="update_status" value="1">
                                    <select name="status" class="form-select form-control-modern"
                                        onchange="this.form.submit()">
                                        <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="Processing" <?= $order['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="Shipped" <?= $order['status'] === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="Completed" <?= $order['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="Cancelled" <?= $order['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                </form>
                            </td>
                            <td data-label="Actions">
                                <div class="action-buttons">
                                    <a href="admin-order-details.php?id=<?= $order['id'] ?>" class="btn btn-action btn-images">
                                        <i class="bi bi-eye me-1"></i>Details
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination-modern">
            <nav>
                <ul class="pagination justify-content-center flex-wrap">
                    <?php
                    $filter_params = http_build_query(['search_id' => $search_id, 'status' => $current_status]);
                    ?>
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link-modern" href="?page=<?= $page - 1 ?>&<?= $filter_params ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link-modern" href="?page=<?= $i ?>&<?= $filter_params ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link-modern" href="?page=<?= $page + 1 ?>&<?= $filter_params ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>



<?php require_once 'includes/footer.php'; ?>
<script>
    // Client-side filter by Order ID (prefix match on the visible #ID)
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('orderSearchInput');
        const tbody = document.getElementById('ordersTableBody');
        if (!input || !tbody) return;

        input.addEventListener('input', function() {
            const q = this.value.trim().toLowerCase();
            const rows = tbody.querySelectorAll('tr');
            rows.forEach(row => {
                const idCell = row.querySelector('td:first-child');
                if (!idCell) return;
                const text = idCell.textContent.toLowerCase();
                row.style.display = text.includes(q) ? '' : 'none';
            });
        });
    });
</script>
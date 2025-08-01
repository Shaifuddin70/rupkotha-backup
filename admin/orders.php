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
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $order_id])) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Order #{$order_id} status updated."];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Failed to update order status.'];
        }
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid order ID or status.'];
    }
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

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="page-title">Manage Orders</h2>
    <form method="get" class="d-flex gap-2">
        <input type="hidden" name="status" value="<?= esc_html($current_status) ?>">
        <input type="text" name="search_id" class="form-control" placeholder="Search by Order ID..."
               value="<?= esc_html($search_id) ?>">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
</div>

<!-- Status Tabs -->
<ul class="nav nav-pills mb-4">
    <?php foreach ($statuses as $status): ?>
        <li class="nav-item">
            <a class="nav-link <?= $current_status === $status ? 'active' : '' ?>" href="?status=<?= $status ?>">
                <?= $status ?>
                <span class="badge bg-light text-dark ms-1">
                    <?= $status === 'All' ? $total_all_orders : ($status_counts[$status] ?? 0) ?>
                </span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<!-- Orders Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <h4>No Orders Found</h4>
                            <p>There are no orders matching the current filter.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="fw-bold">#<?= esc_html($order['id']) ?></td>
                            <td><?= esc_html($order['username'] ?? 'Guest') ?></td>
                            <td><?= format_date($order['created_at']) ?></td>
                            <td><?= formatPrice($order['total_amount']) ?></td>
                            <td><span class="badge bg-secondary"><?= esc_html($order['payment_method']) ?></span></td>
                            <td>
                                <form method="post" class="d-flex gap-2">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <select name="status" class="form-select form-select-sm"
                                            onchange="this.form.submit()">
                                        <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>
                                            Pending
                                        </option>
                                        <option value="Processing" <?= $order['status'] === 'Processing' ? 'selected' : '' ?>>
                                            Processing
                                        </option>
                                        <option value="Shipped" <?= $order['status'] === 'Shipped' ? 'selected' : '' ?>>
                                            Shipped
                                        </option>
                                        <option value="Completed" <?= $order['status'] === 'Completed' ? 'selected' : '' ?>>
                                            Completed
                                        </option>
                                        <option value="Cancelled" <?= $order['status'] === 'Cancelled' ? 'selected' : '' ?>>
                                            Cancelled
                                        </option>
                                    </select>
                                </form>
                            </td>
                            <td class="text-end">
                                <a href="admin-order-details.php?id=<?= $order['id'] ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye-fill"></i> View Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php
                    $filter_params = http_build_query(['search_id' => $search_id, 'status' => $current_status]);
                    ?>
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link"
                           href="?page=<?= $page - 1 ?>&<?= $filter_params ?>"><span>&laquo;</span></a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&<?= $filter_params ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link"
                           href="?page=<?= $page + 1 ?>&<?= $filter_params ?>"><span>&raquo;</span></a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<style>
    .page-title {
        color: #343a40;
    }

    .nav-pills .nav-link {
        color: #6c757d;
    }

    .nav-pills .nav-link.active {
        background-color: #0d6efd;
        box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
    }

    .nav-pills .nav-link .badge {
        transition: all 0.2s ease-in-out;
    }

    .nav-pills .nav-link.active .badge {
        background-color: #fff !important;
        color: #0d6efd !important;
    }
</style>

<?php require_once 'includes/footer.php'; ?>

<?php
// This is the order details page, e.g., order-details

// STEP 1: Start session and include necessary files FIRST.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';

// STEP 2: Authentication and Authorization Checks.
// Redirect if not logged in.
if (!isLoggedIn()) {
    redirect('login?redirect=profile');
}

// Get Order ID from URL and validate it.
$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$order_id) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid order ID.'];
    redirect('profile');
}

$user_id = $_SESSION['user_id'];

// --- DATA FETCHING ---

// 1. Fetch the main order details and verify ownership.
// This is a CRITICAL security check to ensure a user can only see their own orders.
$order_stmt = $pdo->prepare(
    "SELECT o.*, u.username, u.email, u.phone, u.address 
     FROM orders o
     JOIN users u ON o.user_id = u.id
     WHERE o.id = :order_id AND o.user_id = :user_id"
);
$order_stmt->execute([':order_id' => $order_id, ':user_id' => $user_id]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

// If order doesn't exist or doesn't belong to the user, redirect them.
if (!$order) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Order not found or you do not have permission to view it.'];
    redirect('profile');
}

// 2. Fetch the items associated with this order.
$order_items_stmt = $pdo->prepare(
    "SELECT oi.quantity, oi.price, p.name, p.image 
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = :order_id"
);
$order_items_stmt->execute([':order_id' => $order_id]);
$order_items = $order_items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate subtotal from items (more accurate than just storing total)
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
// Assuming a fixed shipping cost was used, as in checkout.php
$shipping_cost = $order['total_amount'] - $subtotal;

// STEP 3: Now, include the header.
include 'includes/header.php';
?>



<div class="modern-header">
    <div class="container">
        <div class="modern-breadcrumb">
            <a href="index">Home</a>
            <span class="separator">•</span>
            <a href="profile">My Account</a>
            <span class="separator">•</span>
            <span>Order Details</span>
        </div>
        <h1 class="order-title">Order #<?= esc_html($order['id']) ?></h1>
        <p class="order-subtitle">Complete order details and invoice</p>
    </div>
</div>

<div class="modern-container mb-5">
    <div class="glass-card">
        <div class="card-header-modern">
            <h2 class="card-title">Order Summary</h2>
            <button onclick="window.print()" class="print-button">
                <svg class="icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zM5 14a1 1 0 001-1v-1h8v1a1 1 0 001 1v2H5v-2z" clip-rule="evenodd" />
                </svg>
                Print Invoice
            </button>
        </div>

        <div class="card-content">
            <div class="info-grid">
                <div class="info-section">
                    <h6>Order Information</h6>
                    <div class="info-item">
                        <span class="info-label">Order ID</span>
                        <span class="info-value">#<?= esc_html($order['id']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Order Date</span>
                        <span class="info-value"><?= format_date($order['created_at']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status</span>
                        <span class="status-badge <?= $order['status'] === 'Completed' ? 'status-completed' : 'status-pending' ?>">
                            <?= esc_html($order['status']) ?>
                        </span>
                    </div>
                </div>

                <div class="info-section">
                    <h6>Shipping Details</h6>
                    <div class="info-item">
                        <span class="info-label">Name</span>
                        <span class="info-value"><?= esc_html($order['username']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Address</span>
                        <span class="info-value"><?= esc_html($order['address']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?= esc_html($order['phone']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?= esc_html($order['email']) ?></span>
                    </div>
                </div>
            </div>

            <div class="items-section">
                <h3 class="section-title">Order Items</h3>

                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td>
                                    <img src="admin/assets/uploads/<?= esc_html($item['image']) ?>"
                                        alt="<?= esc_html($item['name']) ?>"
                                        class="invice-product-image">
                                </td>
                                <td>
                                    <h6 class="product-name"><?= esc_html($item['name']) ?></h6>
                                </td>
                                <td>
                                    <span class="price-text"><?= formatPrice($item['price']) ?></span>
                                </td>
                                <td>
                                    <span class="quantity-badge"><?= esc_html($item['quantity']) ?></span>
                                </td>
                                <td>
                                    <span class="price-text"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="total-section">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span class="price-text"><?= formatPrice($subtotal) ?></span>
                </div>
                <div class="total-row">
                    <span>Shipping</span>
                    <span class="price-text"><?= formatPrice($shipping_cost) ?></span>
                </div>
                <div class="total-row">
                    <span>Grand Total</span>
                    <span><?= formatPrice($order['total_amount']) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div style="text-align: center;">
        <a href="orders" class="back-button">
            <svg class="icon" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Back to Order History
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
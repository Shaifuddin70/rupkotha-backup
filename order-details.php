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



<div class="order-details-page">
    <div class="container">
        <!-- Minimal Header -->
        <div class="order-details-header">
            <div class="header-content">
                <div>
                    <nav class="breadcrumb-minimal">
                        <a href="index">Home</a>
                        <span>/</span>
                        <a href="profile">Profile</a>
                        <span>/</span>
                        <span>Order #<?= esc_html($order['id']) ?></span>
                    </nav>
                    <h1 class="order-number">Order #<?= esc_html($order['id']) ?></h1>
                    <p class="order-date">Placed on <?= format_date($order['created_at'], 'F d, Y') ?></p>
                </div>
                <div class="header-actions">
                    <span class="status-badge-minimal status-<?= strtolower($order['status']) ?>">
                        <?= esc_html($order['status']) ?>
                    </span>
                    <a href="print-order.php?id=<?= $order['id'] ?>" target="_blank" class="btn-print-minimal">
                        <i class="bi bi-printer"></i>
                        Print Invoice
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="order-details-grid">
            <!-- Order Items Section -->
            <div class="order-section order-items-section">
                <h2 class="section-title-minimal">Order Items</h2>
                <div class="order-items-list">
                    <?php foreach ($order_items as $item): ?>
                        <div class="order-item-minimal">
                            <div class="item-image-wrapper">
                                <img src="admin/assets/uploads/<?= esc_html($item['image']) ?>"
                                    alt="<?= esc_html($item['name']) ?>"
                                    class="item-image-minimal">
                            </div>
                            <div class="item-details-minimal">
                                <h3 class="item-name-minimal"><?= esc_html($item['name']) ?></h3>
                                <div class="item-meta-minimal">
                                    <span class="item-price-minimal"><?= formatPrice($item['price']) ?></span>
                                    <span class="item-quantity-minimal">× <?= esc_html($item['quantity']) ?></span>
                                </div>
                            </div>
                            <div class="item-total-minimal">
                                <?= formatPrice($item['price'] * $item['quantity']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="order-sidebar">
                <!-- Order Summary -->
                <div class="order-summary-card">
                    <h3 class="summary-title-minimal">Order Summary</h3>
                    <div class="summary-row-minimal">
                        <span>Subtotal</span>
                        <span><?= formatPrice($subtotal) ?></span>
                    </div>
                    <div class="summary-row-minimal">
                        <span>Shipping</span>
                        <span><?= formatPrice($shipping_cost) ?></span>
                    </div>
                    <div class="summary-row-minimal summary-total">
                        <span>Total</span>
                        <span><?= formatPrice($order['total_amount']) ?></span>
                    </div>
                </div>

                <!-- Shipping Information -->
                <div class="order-info-card">
                    <h3 class="info-title-minimal">Shipping Information</h3>
                    <div class="info-content-minimal">
                        <div class="info-row-minimal">
                            <span class="info-label-minimal">Name</span>
                            <span class="info-value-minimal"><?= esc_html($order['username']) ?></span>
                        </div>
                        <div class="info-row-minimal">
                            <span class="info-label-minimal">Address</span>
                            <span class="info-value-minimal"><?= esc_html($order['address']) ?></span>
                        </div>
                        <div class="info-row-minimal">
                            <span class="info-label-minimal">Phone</span>
                            <span class="info-value-minimal"><?= esc_html($order['phone']) ?></span>
                        </div>
                        <div class="info-row-minimal">
                            <span class="info-label-minimal">Email</span>
                            <span class="info-value-minimal"><?= esc_html($order['email']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Back Button -->
                <a href="orders" class="btn-back-minimal">
                    <i class="bi bi-arrow-left"></i>
                    Back to Orders
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
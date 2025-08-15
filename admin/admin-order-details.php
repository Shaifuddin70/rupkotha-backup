<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// --- Authentication Check ---
if (!isAdmin()) {
    redirect('login.php');
}

// --- Get Order ID and Validate ---
$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$order_id) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid order ID provided.'];
    redirect('orders.php');
}

// --- Handle Order Status Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status = trim($_POST['status']);
    $allowed_statuses = ['Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'];

    if (in_array($status, $allowed_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$status, $order_id])) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Order #{$order_id} status updated to {$status}."];
            } else {
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Failed to update order status.'];
            }
        } catch (Exception $e) {
            error_log("Order status update error (details page): " . $e->getMessage());
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Failed to update order status due to a database error.'];
        }
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid status selected.'];
    }
    redirect('admin-order-details.php?id=' . $order_id);
}

// --- Data Fetching ---
$order_stmt = $pdo->prepare(
    "SELECT o.*, u.username, u.email, u.phone, u.address 
     FROM orders o
     LEFT JOIN users u ON o.user_id = u.id
     WHERE o.id = :order_id"
);
$order_stmt->execute([':order_id' => $order_id]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Order not found.'];
    redirect('orders.php');
}

$order_items_stmt = $pdo->prepare(
    "SELECT oi.quantity, oi.price, p.name, p.image 
     FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = :order_id"
);
$order_items_stmt->execute([':order_id' => $order_id]);
$order_items = $order_items_stmt->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
?>


<div class="custom-container">
    <!-- Header Section -->
    <div class="custom-header no-print">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 2rem;">
            <div>
                <h1 class="order-title">Order #<?= esc_html($order['id']) ?></h1>
                <p class="order-meta">
                    <i class="bi bi-calendar3"></i>
                    Placed on <?= format_date($order['created_at']) ?>
                </p>
            </div>
            <div class="action-buttons">
                <a href="orders.php" class="custom-btn btn-outline">
                    <i class="bi bi-arrow-left"></i>
                    Back to Orders
                </a>
                <a href="mailto:<?= esc_html($order['email']) ?>" class="custom-btn btn-outline">
                    <i class="bi bi-envelope"></i>
                    Email Customer
                </a>
                <button type="button" id="copyAddressBtn" class="custom-btn btn-outline">
                    <i class="bi bi-clipboard"></i>
                    Copy Address
                </button>
                <button onclick="window.print()" class="custom-btn btn-primary">
                    <i class="bi bi-printer-fill"></i>
                    Print Invoice
                </button>
            </div>
        </div>
    </div>

    <!-- Print Header (visible only when printing) -->
    <div style="display: none;" class="print-only">
        <div class="print-header">
            <h1>INVOICE</h1>
            <h2>Order #<?= esc_html($order['id']) ?></h2>
            <p>Date: <?= format_date($order['created_at']) ?></p>
            <p>Status: <?= esc_html($order['status']) ?></p>
        </div>
    </div>

    <div class="grid-layout ">
        <!-- Left Column - Customer & Order Info -->
        <div class="grid-item">


            <!-- Customer Details -->
            <div class="custom-card no-print" style="margin-bottom: 1.5rem;">
                <div class="card-header-custom">
                    <h3 class="card-title-custom">
                        <i class="bi bi-person-circle" style="color: #667eea;"></i>
                        Customer Details
                    </h3>
                </div>
                <div class="card-body-custom">
                    <div class="info-item">
                        <span class="info-label">Customer ID</span>
                        <span class="info-value">#<?= esc_html($order['user_id']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Name</span>
                        <span class="info-value"><?= esc_html($order['username']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email</span>
                        <span class="info-value">
                            <a href="mailto:<?= esc_html($order['email']) ?>" style="color: #667eea; text-decoration: none;">
                                <?= esc_html($order['email']) ?>
                            </a>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone</span>
                        <span class="info-value">
                            <a href="tel:<?= esc_html($order['phone']) ?>" style="color: #667eea; text-decoration: none;">
                                <?= esc_html($order['phone']) ?>
                            </a>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Address</span>
                        <span class="info-value"><?= esc_html($order['address']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="custom-card no-print" style="margin-bottom: 1.5rem;">
                <div class="card-header-custom">
                    <h3 class="card-title-custom">
                        <i class="bi bi-credit-card" style="color: #667eea;"></i>
                        Payment Details
                    </h3>
                </div>
                <div class="card-body-custom">
                    <div class="info-item">
                        <span class="info-label">Payment Method</span>
                        <span class="info-value">
                            <?= ucfirst(esc_html($order['payment_method'])) ?>
                            <?php if ($order['payment_method'] === 'cod'): ?>
                                <span class="badge" style="background: #f59e0b; color: white; font-size: 0.75rem; margin-left: 8px;">Cash on Delivery</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if ($order['payment_method'] !== 'cod' && !empty($order['payment_sender_no'])): ?>
                        <div class="info-item">
                            <span class="info-label">Sender Number</span>
                            <span class="info-value"><?= esc_html($order['payment_sender_no']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Transaction ID</span>
                            <span class="info-value">
                                <code style="background: #f1f5f9; padding: 4px 8px; border-radius: 4px; font-family: monospace;">
                                    <?= esc_html($order['payment_trx_id']) ?>
                                </code>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order Timeline removed per request -->

            <!-- Status Update (No Print) -->
            <div class="custom-card status-update-section no-print">
                <div class="card-header-custom">
                    <h3 class="card-title-custom">
                        <i class="bi bi-arrow-repeat" style="color: #667eea;"></i>
                        Update Status
                    </h3>
                </div>
                <div class="card-body-custom">
                    <form method="post" class="status-form">
                        <select name="status" class="form-select-custom">
                            <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Processing" <?= $order['status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="Shipped" <?= $order['status'] === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="Completed" <?= $order['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="Cancelled" <?= $order['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update_status" class="btn-update">
                            <i class="bi bi-check-circle"></i>
                            Update Status
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column - Invoice -->
        <div class="grid-item">
            <div class="custom-card">
                <div class="card-header-custom">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="card-title-custom">
                            <i class="bi bi-receipt" style="color: #667eea;"></i>
                            Invoice Details
                        </h3>
                        <span class="status-badge-custom status-<?= strtolower($order['status']) ?>">
                            <?= esc_html($order['status']) ?>
                        </span>
                    </div>
                </div>
                <div class="card-body-custom">
                    <!-- Order Items Table -->
                    <div style="margin-bottom: 2rem;">
                        <h4>Ordered Items</h4>
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th style="text-align: right;">Unit Price</th>
                                    <th style="text-align: center;">Qty</th>
                                    <th style="text-align: right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <?php if (!empty($item['image'])): ?>
                                                    <img src="assets/uploads/<?= esc_html($item['image']) ?>"
                                                        alt="<?= esc_html($item['name']) ?>"
                                                        class="product-image">
                                                <?php else: ?>
                                                    <div class="product-image" style="background: #f1f5f9; display: flex; align-items: center; justify-content: center;">
                                                        <i class="bi bi-image" style="color: #94a3b8;"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="product-name">
                                                        <?= esc_html($item['name']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="text-align: right;"><?= formatPrice($item['price']) ?></td>
                                        <td style="text-align: center;">
                                            <span class="badge" style="background: #e2e8f0; color: #475569;">
                                                <?= esc_html($item['quantity']) ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right; font-weight: 600;">
                                            <?= formatPrice($item['price'] * $item['quantity']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Order Summary -->
                    <div class="no-print">
                        <h4>Order Summary</h4>
                        <div class="summary-row">
                            <span>Subtotal (<?= count($order_items) ?> items)</span>
                            <span><?= formatPrice($subtotal) ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping Fee</span>
                            <span><?= formatPrice($order['shipping_fee']) ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Grand Total</span>
                            <span><?= formatPrice($order['total_amount']) ?></span>
                        </div>
                    </div>

                    <!-- Additional Order Info for Print -->
                    <div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem;">
                            <div>
                                <strong>Customer:</strong><br>
                                <?= esc_html($order['username']) ?><br>
                                <?= esc_html($order['email']) ?><br>
                                <?= esc_html($order['phone']) ?>
                            </div>
                            <div>
                                <strong>Shipping Address:</strong><br>
                                <?= esc_html($order['address']) ?><br><br>
                                <strong>Payment:</strong> <?= ucfirst(esc_html($order['payment_method'])) ?>
                                <?php if ($order['payment_method'] !== 'cod' && !empty($order['payment_trx_id'])): ?>
                                    <br><strong>TrxID:</strong> <?= esc_html($order['payment_trx_id']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Footer -->
    <div style="display: none;" class="print-only">
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; font-size: 12px; ">
            <p>Thank you for your business!</p>
            <p>This is a computer-generated invoice. No signature required.</p>
            <p>Printed on: <?= date('F j, Y \a\t g:i A') ?></p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
<script>
    // Enhance: copy address to clipboard for quick paste to couriers
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('copyAddressBtn');
        if (!btn) return;
        btn.addEventListener('click', async function() {
            const address = `<?= addslashes($order['username'] ?? '') ?>\n<?= addslashes($order['address'] ?? '') ?>\n<?= addslashes($order['phone'] ?? '') ?>`;
            try {
                await navigator.clipboard.writeText(address);
                btn.textContent = 'Copied!';
                setTimeout(() => (btn.textContent = 'Copy Address'), 1500);
            } catch (e) {
                alert('Failed to copy address');
            }
        });
    });
</script>
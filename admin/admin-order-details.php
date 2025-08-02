<?php
// admin/admin-order-details.php

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
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $order_id])) {
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Order #{$order_id} status updated to {$status}."];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Failed to update order status.'];
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

<style>
    /* Modern UI Styles */
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        --hover-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        --border-radius: 16px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    .modern-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }

    /* Header Section */
    .modern-header {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        padding: 2rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .modern-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--primary-gradient);
    }

    .order-title {
        font-size: 2.5rem;
        font-weight: 700;
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 0.5rem;
    }

    .order-meta {
        color: #64748b;
        font-size: 1.1rem;
        margin-bottom: 2rem;
    }

    .action-buttons {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .modern-btn {
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        transition: var(--transition);
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 1rem;
    }

    .btn-outline {
        background: white;
        color: #475569;
        border: 2px solid #e2e8f0;
    }

    .btn-outline:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .btn-primary {
        background: var(--primary-gradient);
        color: white;
        border: none;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
    }

    /* Modern Cards */
    .modern-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        transition: var(--transition);
        border: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
    }

    .modern-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--hover-shadow);
    }

    .card-header-modern {
        padding: 1.5rem 2rem 1rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .card-title-modern {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .card-body-modern {
        padding: 1.5rem 2rem 2rem;
    }

    /* Status Badge */
    .status-badge-modern {
        padding: 8px 16px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-pending {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: white;
    }

    .status-processing {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
    }

    .status-shipped {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
    }

    .status-completed {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .status-cancelled {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    /* Info Item */
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: 500;
        color: #64748b;
    }

    .info-value {
        font-weight: 600;
        color: #1e293b;
    }

    /* Modern Table */
    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .modern-table thead {
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    }

    .modern-table th {
        padding: 1rem 1.5rem;
        font-weight: 600;
        color: #475569;
        text-align: left;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .modern-table td {
        padding: 1rem 1.5rem;
        border-top: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .modern-table tbody tr {
        transition: var(--transition);
    }

    .modern-table tbody tr:hover {
        background: #f8fafc;
    }

    /* Summary Section */
    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
    }

    .summary-row.total {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        border-top: 2px solid #e2e8f0;
        margin-top: 1rem;
        padding-top: 1rem;
    }

    /* Status Update Form */
    .status-form {
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        padding: 1.5rem;
        border-radius: 12px;
        margin-top: 1rem;
    }

    .form-select-modern {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 1rem;
        margin-bottom: 1rem;
        transition: var(--transition);
    }

    .form-select-modern:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .btn-update {
        width: 100%;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }

    .btn-update:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
    }

    /* Timeline Styles */
    .timeline {
        position: relative;
        padding-left: 2rem;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e2e8f0;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .timeline-item:last-child {
        margin-bottom: 0;
    }

    .timeline-marker {
        position: absolute;
        left: -23px;
        top: 6px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #e2e8f0;
        border: 3px solid white;
        box-shadow: 0 0 0 2px #e2e8f0;
        transition: var(--transition);
    }

    .timeline-item.completed .timeline-marker {
        background: #10b981;
        box-shadow: 0 0 0 2px #10b981;
    }

    .timeline-content h6 {
        margin: 0 0 4px 0;
        font-weight: 600;
        color: #1e293b;
    }

    .timeline-content p {
        margin: 0;
        color: #64748b;
        font-size: 0.9rem;
    }

    .timeline-item.completed .timeline-content h6 {
        color: #059669;
    }

    /* Product Image Styles */
    .product-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }

    /* Enhanced Badge Styles */
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Responsive Grid */
    .grid-layout {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    /* Print Styles */
    @media print {
        * {
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        body {
            background: white !important;
            font-size: 12px;
            color: #000 !important;
        }

        body>nav,
        #layoutSidenav_nav,
        .sb-sidenav,
        .no-print,
        .status-update-section {
            display: none !important;
        }

        #layoutSidenav,
        #layoutSidenav_content {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            position: static !important;
            transform: none !important;
        }

        .modern-container {
            max-width: 100% !important;
            padding: 20px !important;
            margin: 0 !important;
        }

        .modern-header {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            margin-bottom: 20px !important;
            page-break-after: avoid;
            background: white !important;
        }

        .modern-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            margin-bottom: 15px !important;
            page-break-inside: avoid;
            background: white !important;
        }

        .modern-table {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            background: white !important;
        }

        .modern-table th,
        .modern-table td {
            border: 1px solid #ddd !important;
            padding: 8px 12px !important;
            background: white !important;
            color: #000 !important;
        }

        .modern-table th {
            background: #f5f5f5 !important;
            font-weight: bold !important;
        }

        .status-badge-modern {
            border: 1px solid #333 !important;
            color: #000 !important;
            background: #f0f0f0 !important;
        }

        .grid-layout {
            display: block !important;
        }

        .grid-item {
            width: 100% !important;
            margin-bottom: 15px !important;
        }

        .print-only {
            display: block !important;
        }

        .timeline::before {
            background: #333 !important;
        }

        .timeline-marker {
            border-color: white !important;
            box-shadow: 0 0 0 2px #333 !important;
            background: #f0f0f0 !important;
        }

        .timeline-item.completed .timeline-marker {
            background: #333 !important;
            box-shadow: 0 0 0 2px #333 !important;
        }

        .product-image {
            display: none !important;
        }

        /* Force all text to be black */
        * {
            color: #000 !important;
        }

        /* Ensure summary section prints properly */
        .summary-row {
            border-bottom: 1px solid #ddd !important;
            padding: 8px 0 !important;
        }

        .summary-row.total {
            border-top: 2px solid #333 !important;
            border-bottom: 2px solid #333 !important;
            font-weight: bold !important;
        }

        /* Company header for print */
        .print-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #333;
        }

        .print-header h1 {
            font-size: 28px !important;
            font-weight: bold !important;
            margin: 0 0 10px 0 !important;
        }

        .print-header h2 {
            font-size: 20px !important;
            margin: 0 0 5px 0 !important;
        }
    }

    @media (max-width: 968px) {
        .grid-layout {
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .modern-container {
            padding: 1rem;
        }

        .order-title {
            font-size: 2rem;
        }

        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<div class="modern-container">
    <!-- Header Section -->
    <div class="modern-header no-print">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 2rem;">
            <div>
                <h1 class="order-title">Order #<?= esc_html($order['id']) ?></h1>
                <p class="order-meta">
                    <i class="bi bi-calendar3"></i>
                    Placed on <?= format_date($order['created_at']) ?>
                </p>
            </div>
            <div class="action-buttons">
                <a href="orders.php" class="modern-btn btn-outline">
                    <i class="bi bi-arrow-left"></i>
                    Back to Orders
                </a>
                <button onclick="window.print()" class="modern-btn btn-primary">
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
            <div class="modern-card no-print" style="margin-bottom: 1.5rem;">
                <div class="card-header-modern">
                    <h3 class="card-title-modern">
                        <i class="bi bi-person-circle" style="color: #667eea;"></i>
                        Customer Details
                    </h3>
                </div>
                <div class="card-body-modern">
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

            <!-- Payment Details
            <div class="modern-card no-print" style="margin-bottom: 1.5rem;">
                <div class="card-header-modern">
                    <h3 class="card-title-modern">
                        <i class="bi bi-credit-card" style="color: #667eea;"></i>
                        Payment Details
                    </h3>
                </div>
                <div class="card-body-modern">
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
            </div> -->

            <!-- Order Timeline
            <div class="modern-card no-print" style="margin-bottom: 1.5rem;">
                <div class="card-header-modern">
                    <h3 class="card-title-modern">
                        <i class="bi bi-clock-history" style="color: #667eea;"></i>
                        Order Timeline
                    </h3>
                </div>
                <div class="card-body-modern">
                    <div class="timeline">
                        <div class="timeline-item <?= in_array($order['status'], ['Pending', 'Processing', 'Shipped', 'Completed']) ? 'completed' : '' ?>">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6>Order Placed</h6>
                                <p><?= format_date($order['created_at']) ?></p>
                            </div>
                        </div>
                        <div class="timeline-item <?= in_array($order['status'], ['Processing', 'Shipped', 'Completed']) ? 'completed' : '' ?>">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6>Processing</h6>
                                <p>Order being prepared</p>
                            </div>
                        </div>
                        <div class="timeline-item <?= in_array($order['status'], ['Shipped', 'Completed']) ? 'completed' : '' ?>">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6>Shipped</h6>
                                <p>Order dispatched</p>
                            </div>
                        </div>
                        <div class="timeline-item <?= $order['status'] === 'Completed' ? 'completed' : '' ?>">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6>Delivered</h6>
                                <p>Order completed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->

            <!-- Status Update (No Print) -->
            <div class="modern-card status-update-section no-print">
                <div class="card-header-modern">
                    <h3 class="card-title-modern">
                        <i class="bi bi-arrow-repeat" style="color: #667eea;"></i>
                        Update Status
                    </h3>
                </div>
                <div class="card-body-modern">
                    <form method="post" class="status-form">
                        <select name="status" class="form-select-modern">
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
            <div class="modern-card">
                <div class="card-header-modern">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="card-title-modern">
                            <i class="bi bi-receipt" style="color: #667eea;"></i>
                            Invoice Details
                        </h3>
                        <span class="status-badge-modern status-<?= strtolower($order['status']) ?>">
                            <?= esc_html($order['status']) ?>
                        </span>
                    </div>
                </div>
                <div class="card-body-modern">
                    <!-- Order Items Table -->
                    <div style="margin-bottom: 2rem;">
                        <h4 style="margin-bottom: 1rem; color: #1e293b; font-weight: 600;">Ordered Items</h4>
                        <table class="modern-table">
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
                                                    <div style="font-weight: 600; color: #1e293b;">
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
                    <div class="no-print" style="margin-top: 2rem; padding: 1.5rem; background: #f8fafc; border-radius: 12px;">
                        <h4 style="margin-bottom: 1rem; color: #1e293b; font-weight: 600;">Order Summary</h4>
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
                    <div style="margin-top: 2rem; padding: 1rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #fafbfc;">
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
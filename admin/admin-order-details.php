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
    .details-card {
        margin-bottom: 1.5rem;
    }

    .invoice-area .table tfoot {
        border-top: 2px solid #dee2e6;
    }

    .status-badge {
        font-size: 1rem;
        padding: .5em .8em;
    }

    /* --- Print-Specific CSS --- */
    @media print {
        /* Hide elements not part of the invoice */
        body > #nav,
        body > #layoutSidenav,
        .page-header,
        .status-update-card,
        .no-print {
            display: none !important;
        }

        /* Ensure the main content area and invoice take up full space */
        body {
            background-color: #fff !important;
        }

        #layoutSidenav_content {
            margin-left: 0 !important;
            padding: 0 !important;
        }

        .container-fluid {
            padding: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        /* Style the invoice for printing */
        .invoice-area {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        .invoice-area .card-header, .invoice-area .card-body {
            padding: 1.5rem;
        }

        /* Ensure text is black for printers */
        .invoice-area * {
            color: #000 !important;
        }

        /* Help Bootstrap badges print their background colors */
        .badge {
            -webkit-print-color-adjust: exact; /* Chrome, Safari */
            color-adjust: exact; /* Firefox */
            border: 1px solid #6c757d;
        }
    }
</style>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center my-4">
        <div>
            <h1 class="page-title">Order #<?= esc_html($order['id']) ?></h1>
            <p class="text-muted mb-0">Order placed on: <?= format_date($order['created_at']) ?></p>
        </div>
        <div class="no-print">
            <a href="orders.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Orders
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer-fill"></i> Print Invoice
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Customer and Status -->
        <div class="col-lg-4">
            <div class="card details-card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Customer Details</h5>
                    <p class="mb-1"><strong>Name:</strong> <?= esc_html($order['username']) ?></p>
                    <p class="mb-1"><strong>Email:</strong> <a
                                href="mailto:<?= esc_html($order['email']) ?>"><?= esc_html($order['email']) ?></a></p>
                    <p class="mb-0"><strong>Phone:</strong> <a
                                href="tel:<?= esc_html($order['phone']) ?>"><?= esc_html($order['phone']) ?></a></p>
                </div>
            </div>
            <div class="card details-card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Shipping & Payment</h5>
                    <p class="mb-1"><strong>Address:</strong> <?= esc_html($order['address']) ?></p>
                    <hr>
                    <p class="mb-1"><strong>Payment Method:</strong> <?= esc_html($order['payment_method']) ?></p>
                    <?php if ($order['payment_method'] !== 'cod' && !empty($order['payment_sender_no'])): ?>
                        <p class="mb-1"><strong>Sender No:</strong> <?= esc_html($order['payment_sender_no']) ?></p>
                        <p class="mb-0"><strong>TrxID:</strong> <?= esc_html($order['payment_trx_id']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card details-card status-update-card no-print">
                <div class="card-body">
                    <h5 class="card-title mb-3">Update Order Status</h5>
                    <form method="post">
                        <select name="status" class="form-select mb-2">
                            <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : '' ?>>Pending
                            </option>
                            <option value="Processing" <?= $order['status'] === 'Processing' ? 'selected' : '' ?>>
                                Processing
                            </option>
                            <option value="Shipped" <?= $order['status'] === 'Shipped' ? 'selected' : '' ?>>Shipped
                            </option>
                            <option value="Completed" <?= $order['status'] === 'Completed' ? 'selected' : '' ?>>
                                Completed
                            </option>
                            <option value="Cancelled" <?= $order['status'] === 'Cancelled' ? 'selected' : '' ?>>
                                Cancelled
                            </option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-success w-100">Update Status</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Right Column: Invoice -->
        <div class="col-lg-8">
            <div class="card invoice-area">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Invoice</h5>
                    <span class="status-badge badge <?=
                    match ($order['status']) {
                        'Completed' => 'bg-success',
                        'Pending' => 'bg-warning text-dark',
                        'Processing' => 'bg-info text-dark',
                        'Shipped' => 'bg-primary',
                        'Cancelled' => 'bg-danger',
                        default => 'bg-secondary'
                    }
                    ?>"><?= esc_html($order['status']) ?></span>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td><?= esc_html($item['name']) ?></td>
                                    <td class="text-end"><?= formatPrice($item['price']) ?></td>
                                    <td class="text-center"><?= esc_html($item['quantity']) ?></td>
                                    <td class="text-end fw-bold"><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                            <tr>
                                <td colspan="3" class="text-end border-0">Subtotal</td>
                                <td class="text-end border-0"><?= formatPrice($subtotal) ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end border-0">Shipping Fee</td>
                                <td class="text-end border-0"><?= formatPrice($order['shipping_fee']) ?></td>
                            </tr>
                            <tr class="fw-bold fs-5">
                                <td colspan="3" class="text-end">Grand Total</td>
                                <td class="text-end"><?= formatPrice($order['total_amount']) ?></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<?php
// Print-friendly order invoice page

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

// Calculate subtotal from items
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping_cost = $order['total_amount'] - $subtotal;

// Get company settings for invoice header
$settings_stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $settings_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$companyName = $settings['company_name'] ?? 'Rupkotha';
$companyLogo = !empty($settings['logo']) ? 'admin/assets/uploads/' . esc_html($settings['logo']) : 'assets/images/default-logo.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - Order #<?= esc_html($order['id']) ?> - <?= esc_html($companyName) ?></title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: #1a1a1a;
            background: white;
            padding: 2rem;
            line-height: 1.6;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .company-info {
            flex: 1;
        }

        .company-logo {
            max-width: 120px;
            height: auto;
            margin-bottom: 1rem;
        }

        .company-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .invoice-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .invoice-meta {
            font-size: 0.875rem;
            color: #64748b;
        }

        .order-info {
            text-align: right;
        }

        .order-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #bea163;
            margin-bottom: 0.5rem;
        }

        .order-date {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.75rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-completed {
            background: rgba(16, 185, 129, 0.1);
            color: #065f46;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #92400e;
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.1);
            color: #991b1b;
        }

        .invoice-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 1rem;
        }

        .section-content {
            font-size: 0.95rem;
            color: #1a1a1a;
        }

        .section-content p {
            margin-bottom: 0.5rem;
        }

        .section-content strong {
            color: #1a1a1a;
        }

        .items-section {
            margin-bottom: 2rem;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        .items-table thead {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        .items-table th {
            padding: 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
        }

        .items-table td {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .item-product {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .item-name {
            font-weight: 600;
            color: #1a1a1a;
        }

        .item-price,
        .item-quantity,
        .item-total {
            color: #1a1a1a;
            font-weight: 500;
        }

        .item-total {
            font-weight: 700;
            color: #bea163;
        }

        .totals-section {
            margin-left: auto;
            max-width: 300px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.95rem;
        }

        .total-row:last-child {
            border-bottom: none;
        }

        .total-row.grand-total {
            margin-top: 0.5rem;
            padding-top: 1rem;
            border-top: 2px solid #e2e8f0;
            font-size: 1.125rem;
            font-weight: 700;
            color: #1a1a1a;
        }

        .total-row.grand-total .total-value {
            color: #bea163;
            font-size: 1.25rem;
        }

        .total-label {
            color: #64748b;
        }

        .total-value {
            color: #1a1a1a;
            font-weight: 600;
        }

        .invoice-footer {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            font-size: 0.875rem;
            color: #64748b;
        }

        @media print {
            body {
                padding: 0;
            }

            .invoice-container {
                max-width: 100%;
            }

            @page {
                size: A4;
                margin: 1.5cm;
            }

            .no-print {
                display: none !important;
            }
        }

        @media screen {
            .print-button {
                position: fixed;
                top: 2rem;
                right: 2rem;
                padding: 0.75rem 1.5rem;
                background: #bea163;
                color: white;
                border: none;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                box-shadow: 0 4px 12px rgba(190, 161, 99, 0.3);
                z-index: 1000;
            }

            .print-button:hover {
                background: #a68a4f;
            }
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">
        <i class="bi bi-printer"></i> Print Invoice
    </button>

    <div class="invoice-container">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="company-info">
                <?php if (file_exists($companyLogo)): ?>
                    <img src="<?= esc_html($companyLogo) ?>" alt="<?= esc_html($companyName) ?>" class="company-logo">
                <?php endif; ?>
                <div class="company-name"><?= esc_html($companyName) ?></div>
                <div class="invoice-title">Invoice</div>
            </div>
            <div class="order-info">
                <div class="order-number">Order #<?= esc_html($order['id']) ?></div>
                <div class="order-date"><?= format_date($order['created_at'], 'F d, Y') ?></div>
                <span class="status-badge status-<?= strtolower($order['status']) ?>">
                    <?= esc_html($order['status']) ?>
                </span>
            </div>
        </div>

        <!-- Customer & Shipping Info -->
        <div class="invoice-sections">
            <div>
                <div class="section-title">Bill To</div>
                <div class="section-content">
                    <p><strong><?= esc_html($order['username']) ?></strong></p>
                    <p><?= esc_html($order['email']) ?></p>
                    <p><?= esc_html($order['phone']) ?></p>
                </div>
            </div>
            <div>
                <div class="section-title">Ship To</div>
                <div class="section-content">
                    <p><strong><?= esc_html($order['username']) ?></strong></p>
                    <p><?= esc_html($order['address']) ?></p>
                    <p><?= esc_html($order['phone']) ?></p>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="items-section">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="text-align: right;">Price</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td>
                                <div class="item-product">
                                    <img src="admin/assets/uploads/<?= esc_html($item['image']) ?>"
                                        alt="<?= esc_html($item['name']) ?>"
                                        class="item-image">
                                    <span class="item-name"><?= esc_html($item['name']) ?></span>
                                </div>
                            </td>
                            <td style="text-align: right;" class="item-price"><?= formatPrice($item['price']) ?></td>
                            <td style="text-align: center;" class="item-quantity"><?= esc_html($item['quantity']) ?></td>
                            <td style="text-align: right;" class="item-total"><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals-section">
            <div class="total-row">
                <span class="total-label">Subtotal</span>
                <span class="total-value"><?= formatPrice($subtotal) ?></span>
            </div>
            <div class="total-row">
                <span class="total-label">Shipping</span>
                <span class="total-value"><?= formatPrice($shipping_cost) ?></span>
            </div>
            <div class="total-row grand-total">
                <span class="total-label">Total</span>
                <span class="total-value"><?= formatPrice($order['total_amount']) ?></span>
            </div>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            <p>Thank you for your business!</p>
            <p>This is a computer-generated invoice. No signature required.</p>
        </div>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            // Small delay to ensure page is fully loaded
            setTimeout(function() {
                window.print();
            }, 250);
        };

        // Close the window/tab when print dialog is closed
        // This works by detecting when the print dialog is closed
        let beforePrint = false;
        
        window.addEventListener('beforeprint', function() {
            beforePrint = true;
        });

        window.addEventListener('afterprint', function() {
            if (beforePrint) {
                // Close the window/tab after print dialog is closed
                window.close();
                
                // Fallback: if window.close() doesn't work (some browsers block it),
                // try to redirect back or show a message
                setTimeout(function() {
                    if (!document.hidden) {
                        // If window is still open, try to close it
                        window.close();
                    }
                }, 100);
            }
        });

        // Alternative method: Listen for focus events after print
        let printInProgress = false;
        
        window.addEventListener('beforeprint', function() {
            printInProgress = true;
        });

        window.addEventListener('afterprint', function() {
            printInProgress = false;
            // Close after a short delay to ensure print is complete
            setTimeout(function() {
                window.close();
            }, 500);
        });

        // Handle cases where afterprint might not fire
        window.addEventListener('focus', function() {
            if (printInProgress) {
                setTimeout(function() {
                    window.close();
                }, 1000);
            }
        });
    </script>
</body>
</html>

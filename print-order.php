<?php
// Standalone printable invoice page for customers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Ensure logged in
if (!isLoggedIn()) {
    redirect('login?redirect=orders');
}

// Validate order id and ownership
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$orderId) {
    redirect('orders');
}

$userId = $_SESSION['user_id'];

// Fetch settings (company info)
$settings_stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $settings_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$companyName = $settings['company_name'] ?? 'Rupkotha';
$companyLogo = !empty($settings['logo']) ? 'admin/assets/uploads/' . esc_html($settings['logo']) : 'assets/images/logo.jpg';

// Fetch order and verify owner
$order_stmt = $pdo->prepare(
    "SELECT o.*, u.username, u.email, u.phone, u.address
     FROM orders o
     JOIN users u ON o.user_id = u.id
     WHERE o.id = :order_id AND o.user_id = :user_id"
);
$order_stmt->execute([':order_id' => $orderId, ':user_id' => $userId]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    redirect('orders');
}

// Fetch order items
$items_stmt = $pdo->prepare(
    "SELECT oi.quantity, oi.price, p.name, p.image
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = :order_id"
);
$items_stmt->execute([':order_id' => $orderId]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

$subtotal = 0;
foreach ($items as $it) {
    $subtotal += (float)$it['price'] * (int)$it['quantity'];
}
$shipping = (float)$order['total_amount'] - $subtotal;

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice #<?= esc_html($order['id']) ?> - <?= esc_html($companyName) ?></title>
    <style>
        :root {
            --border: #d1d5db;
            --text: #111827;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            color: var(--text);
            background: #ffffff;
        }

        .invoice-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--border);
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand img {
            height: 48px;
            width: auto;
        }

        .title {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
        }

        .meta {
            margin-top: 4px;
            font-size: 14px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }

        .card {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px;
        }

        .card h3 {
            margin: 0 0 12px;
            font-size: 16px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin: 6px 0;
            font-size: 14px;
        }

        .label {
            color: #374151;
        }

        .value {
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        thead th {
            text-align: left;
            border-bottom: 2px solid var(--border);
            padding: 10px 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .03em;
        }

        tbody td {
            border-bottom: 1px solid var(--border);
            padding: 10px 8px;
            font-size: 14px;
            vertical-align: top;
        }

        .totals {
            margin-top: 12px;
            width: 100%;
            max-width: 400px;
            margin-left: auto;
        }

        .totals .row {
            margin: 4px 0;
        }

        .grand {
            font-size: 18px;
            border-top: 2px solid var(--border);
            padding-top: 8px;
            margin-top: 6px;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            margin-top: 24px;
            color: #6b7280;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            @page {
                size: auto;
                margin: 12mm;
            }

            .no-print {
                display: none !important;
            }

            html,
            body {
                height: auto !important;
                overflow: visible !important;
            }
        }

        .actions {
            text-align: right;
            margin-bottom: 12px;
        }

        .btn {
            display: inline-block;
            background: #111827;
            color: #fff;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }

        .btn:hover {
            opacity: .9;
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <div class="actions no-print">
            <a href="javascript:window.print()" class="btn">Print</a>
        </div>

        <div class="invoice-header">
            <div class="brand">
                <img src="<?= $companyLogo ?>" alt="<?= esc_html($companyName) ?>" />
                <div>
                    <h1 class="title">INVOICE</h1>
                    <div class="meta">Order #<?= esc_html($order['id']) ?> • <?= format_date($order['created_at']) ?></div>
                </div>
            </div>
            <div style="text-align:right; font-size:14px;">
                <div><strong>Status:</strong> <?= esc_html($order['status']) ?></div>
                <div><strong>Method:</strong> <?= ucfirst(esc_html($order['payment_method'])) ?></div>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <h3>Sold To</h3>
                <div class="row"><span class="label">Name</span><span class="value"><?= esc_html($order['username']) ?></span></div>
                <div class="row"><span class="label">Email</span><span class="value"><?= esc_html($order['email']) ?></span></div>
                <div class="row"><span class="label">Phone</span><span class="value"><?= esc_html($order['phone']) ?></span></div>
            </div>
            <div class="card">
                <h3>Ship To</h3>
                <div class="row"><span class="label">Address</span><span class="value" style="max-width: 60%; text-align:right;"><?= esc_html($order['address']) ?></span></div>
            </div>
        </div>

        <div class="card">
            <h3>Items</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width:45%;">Product</th>
                        <th style="width:15%;">Unit Price</th>
                        <th style="width:10%;">Qty</th>
                        <th style="width:15%; text-align:right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><?= esc_html($it['name']) ?></td>
                            <td><?= formatPrice($it['price']) ?></td>
                            <td><?= (int)$it['quantity'] ?></td>
                            <td style="text-align:right; font-weight:600;"><?= formatPrice($it['price'] * $it['quantity']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="totals">
                <div class="row"><span class="label">Subtotal</span><span class="value"><?= formatPrice($subtotal) ?></span></div>
                <div class="row"><span class="label">Shipping</span><span class="value"><?= formatPrice($shipping) ?></span></div>
                <div class="row grand"><span class="label">Grand Total</span><span class="value"><?= formatPrice($order['total_amount']) ?></span></div>
            </div>
        </div>

        <div class="footer">
            <p>Thank you for your purchase!</p>
            <p>This invoice was generated on <?= date('F j, Y \a\t g:i A') ?>.</p>
        </div>
    </div>

    <script>
        // Auto-open print in a new tab
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>

</html>
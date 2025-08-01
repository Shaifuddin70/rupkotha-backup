<?php
require_once 'includes/header.php';
require_once 'includes/functions.php'; // Using functions like formatPrice()

// Ensure admin is logged in
if (!isAdmin()) {
    redirect('login.php');
}

// --- DATA FETCHING FOR DASHBOARD WIDGETS ---

// 1. Total Profit from completed orders
// This calculation assumes you have a 'cost_price' column in your 'products' table.
// Profit = Total Revenue - Total Cost of Goods Sold (COGS)

// First, get the total revenue from completed orders
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'Completed'")->fetchColumn();

// Second, get the total cost of goods sold (COGS) for those orders
// CORRECTED a typo here: o.ida -> o.id
$cogs_query = "SELECT SUM(oi.quantity * p.cost_price)
               FROM order_items oi
               JOIN products p ON oi.product_id = p.id
               JOIN orders o ON oi.order_id = o.id
               WHERE o.status = 'Completed'";
$total_cogs = $pdo->query($cogs_query)->fetchColumn();

// Calculate the final profit
$total_profit = ($total_revenue ?? 0) - ($total_cogs ?? 0);


// 2. Total Orders
$total_orders = $pdo->query("SELECT COUNT(id) FROM orders")->fetchColumn();

// 3. Total Customers
$total_customers = $pdo->query("SELECT COUNT(id) FROM users")->fetchColumn();

// 4. Total Products
$total_products = $pdo->query("SELECT COUNT(id) FROM products")->fetchColumn();


// --- DATA FOR "RECENT ORDERS" TABLE ---
$recent_orders_stmt = $pdo->query(
    "SELECT o.id, o.total_amount, o.status, o.created_at, u.username
     FROM orders o
     LEFT JOIN users u ON o.user_id = u.id
     ORDER BY o.created_at DESC
     LIMIT 5"
);
$recent_orders = $recent_orders_stmt->fetchAll(PDO::FETCH_ASSOC);


// --- DATA FOR "TOP SELLING PRODUCTS" TABLE ---
$top_products_stmt = $pdo->query(
    "SELECT p.name, p.image, SUM(oi.quantity) as total_sold
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     GROUP BY p.id, p.name, p.image
     ORDER BY total_sold DESC
     LIMIT 5"
);
$top_products = $top_products_stmt->fetchAll(PDO::FETCH_ASSOC);


// --- DATA FOR SALES CHART (Last 7 Days) ---
$sales_data_stmt = $pdo->query(
    "SELECT DATE(created_at) as sale_date, SUM(total_amount) as daily_total
     FROM orders
     WHERE created_at >= CURDATE() - INTERVAL 7 DAY AND status = 'Completed'
     GROUP BY DATE(created_at)
     ORDER BY sale_date ASC"
);
$sales_data = $sales_data_stmt->fetchAll(PDO::FETCH_ASSOC);

// Format data for Chart.js
$chart_labels = [];
$sales_map = [];
// Create a map of the last 7 days with 0 sales
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('D, M j', strtotime($date));
    $sales_map[$date] = 0;
}
// Fill the map with actual sales data
foreach ($sales_data as $data) {
    if (isset($sales_map[$data['sale_date']])) {
        $sales_map[$data['sale_date']] = $data['daily_total'];
    }
}
$chart_values = array_values($sales_map);

?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="container-fluid px-4">
    <h1 class="mt-4">Dashboard</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Overview</li>
    </ol>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Profit
                                (Completed)
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= formatPrice($total_profit ?? 0) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-cash-coin fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Orders</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($total_orders ?? 0) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-cart4 fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Customers</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($total_customers ?? 0) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people-fill fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Products</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($total_products ?? 0) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-box-seam fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Sales Overview (Last 7 Days)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Selling Products</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($top_products)): ?>
                        <p class="text-center text-muted mt-3">No sales data yet.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($top_products as $product): ?>
                                <li class="list-group-item d-flex align-items-center">
                                    <img src="assets/uploads/<?= esc_html($product['image']) ?>"
                                         alt="<?= esc_html($product['name']) ?>" class="rounded me-3"
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-0"><?= esc_html($product['name']) ?></h6>
                                        <small class="text-muted"><?= esc_html($product['total_sold']) ?> units
                                            sold</small>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recent_orders)): ?>
                        <tr>
                            <td colspan="5" class="text-center">No recent orders found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td>#<?= esc_html($order['id']) ?></td>
                                <td><?= esc_html($order['username'] ?? 'Guest') ?></td>
                                <td><?= formatPrice($order['total_amount']) ?></td>
                                <td>
                                        <span class="badge
                                            <?= $order['status'] === 'Completed' ? 'bg-success' : '' ?>
                                            <?= $order['status'] === 'Pending' ? 'bg-warning text-dark' : '' ?>
                                            <?= $order['status'] === 'Cancelled' ? 'bg-danger' : '' ?>">
                                            <?= esc_html($order['status']) ?>
                                        </span>
                                </td>
                                <td><?= format_date($order['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Chart.js Configuration
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Sales',
                    data: <?= json_encode($chart_values) ?>,
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: "rgba(78, 115, 223, 1)",
                    pointBorderColor: "rgba(78, 115, 223, 1)",
                    tension: 0.3,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '৳' + new Intl.NumberFormat('en-IN').format(value);
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return `Sales: ${formatPrice(context.parsed.y)}`;
                            }
                        }
                    }
                }
            }
        });

        // Helper function for tooltip formatting
        function formatPrice(price) {
            return '৳ ' + new Intl.NumberFormat('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(price);
        }
    });
</script>

<?php
require_once 'includes/footer.php';
?>

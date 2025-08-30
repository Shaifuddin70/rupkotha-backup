<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';


if (!isAdmin()) {
    redirect('login.php');
}


$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'Completed'")->fetchColumn();


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
     LIMIT 4"
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
<div class="dashboard-container">


    <!-- Modern Stats Cards -->
    <div class="row mb-5">
        <div class="col-lg-3 col-md-6 mb-5 mb-lg-0">
            <div class=" modern-stat-card stat-card-profit">
                <div class="stat-icon-container">
                    <i class="bi bi-cash-coin stat-icon"></i>
                </div>
                <div class="stat-label">Total Profit (Completed)</div>
                <div class="stat-value"><?= formatPrice($total_profit ?? 0) ?></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-5 mb-lg-0">

            <div class=" modern-stat-card stat-card-orders">
                <div class="stat-icon-container">
                    <i class="bi bi-cart4 stat-icon"></i>
                </div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?= number_format($total_orders ?? 0) ?></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-5 mb-md-0">
            <div class=" modern-stat-card stat-card-customers">
                <div class="stat-icon-container">
                    <i class="bi bi-people-fill stat-icon"></i>
                </div>
                <div class="stat-label">Total Customers</div>
                <div class="stat-value"><?= number_format($total_customers ?? 0) ?></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class=" modern-stat-card stat-card-products">
                <div class="stat-icon-container">
                    <i class="bi bi-box-seam stat-icon"></i>
                </div>
                <div class="stat-label">Total Products</div>
                <div class="stat-value"><?= number_format($total_products ?? 0) ?></div>
            </div>
        </div>

    </div>

    <!-- Main Content Grid -->
    <div class="content-grid">
        <!-- Sales Chart -->
        <div class="modern-card">
            <div class="modern-card-header">
                <h3 class="modern-card-title">📈 Sales Overview (Last 7 Days)</h3>
            </div>
            <div class="modern-card-body">
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="modern-card">
            <div class="modern-card-header">
                <h3 class="modern-card-title">🏆 Top Selling Products</h3>
            </div>
            <div class="modern-card-body">
                <?php if (empty($top_products)): ?>
                    <div class="no-data-modern">
                        🛍️ No sales data available yet
                    </div>
                <?php else: ?>
                    <div class="modern-product-list">
                        <?php foreach ($top_products as $product): ?>
                            <div class="modern-product-item">
                                <img src="assets/uploads/<?= esc_html($product['image']) ?>"
                                    alt="<?= esc_html($product['name']) ?>"
                                    class="product-image-modern"
                                    onerror="this.src='https://via.placeholder.com/64x64/e5e7eb/9ca3af?text=No+Image'">
                                <div class="product-info-modern">
                                    <h6><?= esc_html($product['name']) ?></h6>
                                    <small><?= number_format($product['total_sold']) ?> units sold</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="modern-orders-card">
        <div class="modern-card-header">
            <h3 class="modern-card-title">🕒 Recent Orders</h3>
        </div>
        <div class="modern-card-body">
            <div class="modern-table-container">
                <table class="modern-table">
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
                                <td colspan="5" class="no-data-modern">
                                    📋 No recent orders found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td><strong>#<?= esc_html($order['id']) ?></strong></td>
                                    <td><?= esc_html($order['username'] ?? 'Guest User') ?></td>
                                    <td><strong><?= formatPrice($order['total_amount']) ?></strong></td>
                                    <td>
                                        <span class="modern-status-badge 
                                            <?= $order['status'] === 'Completed' ? 'status-completed' : '' ?>
                                            <?= $order['status'] === 'Pending' ? 'status-pending' : '' ?>
                                            <?= $order['status'] === 'Cancelled' ? 'status-cancelled' : '' ?>">
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
    document.addEventListener("DOMContentLoaded", function() {
        // Chart.js Configuration with your real data
        const ctx = document.getElementById('salesChart').getContext('2d');

        // Create gradient for the chart
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(139, 92, 246, 0.4)');
        gradient.addColorStop(1, 'rgba(139, 92, 246, 0.05)');

        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Sales',
                    data: <?= json_encode($chart_values) ?>,
                    backgroundColor: gradient,
                    borderColor: '#8b5cf6',
                    borderWidth: 4,
                    pointRadius: 8,
                    pointBackgroundColor: '#8b5cf6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 4,
                    pointHoverRadius: 12,
                    tension: 0.4,
                    fill: true,
                    shadowColor: 'rgba(139, 92, 246, 0.3)',
                    shadowBlur: 20,
                    shadowOffsetY: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(17, 24, 39, 0.95)',
                        titleColor: '#f3f4f6',
                        bodyColor: '#f3f4f6',
                        borderColor: '#8b5cf6',
                        borderWidth: 2,
                        cornerRadius: 16,
                        padding: 16,
                        titleFont: {
                            size: 14,
                            weight: '600'
                        },
                        bodyFont: {
                            size: 13,
                            weight: '500'
                        },
                        callbacks: {
                            label: function(context) {
                                return `Sales: ${formatPrice(context.parsed.y)}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        border: {
                            display: false,
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 13,
                                weight: '600'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false,
                        },
                        border: {
                            display: false,
                        },
                        ticks: {
                            color: '#6b7280',
                            font: {
                                size: 13,
                                weight: '600'
                            },
                            callback: function(value) {
                                return '৳' + new Intl.NumberFormat('en-IN').format(value);
                            }
                        }
                    }
                }
            }
        });

        // Helper function for tooltip formatting (matches your PHP function)
        function formatPrice(price) {
            return '৳ ' + new Intl.NumberFormat('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(price);
        }

        // Animate stats cards on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0) scale(1)';
                    }, index * 100);
                }
            });
        }, observerOptions);

        // // Apply animation to stat cards
        // document.querySelectorAll('.modern-stat-card').forEach(card => {
        //     card.style.opacity = '0';
        //     card.style.transform = 'translateY(30px) scale(0.95)';
        //     card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
        //     observer.observe(card);
        // });

        // Add loading shimmer effect for product images
        document.querySelectorAll('.product-image-modern').forEach(img => {
            img.addEventListener('load', function() {
                this.classList.remove('loading-shimmer');
            });
            img.addEventListener('error', function() {
                this.classList.remove('loading-shimmer');
            });
            img.classList.add('loading-shimmer');
        });
    });
</script>

<?php
require_once 'includes/footer.php';
?>
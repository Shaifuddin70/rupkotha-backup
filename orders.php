<?php
// This is the customer's order history page, e.g., orders.php

// STEP 1: Start session and include necessary files FIRST.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';

// STEP 2: Authentication Check. Redirect if not logged in.
if (!isLoggedIn()) {
    redirect('login.php?redirect=orders.php');
}

$user_id = $_SESSION['user_id'];

// --- DATA FETCHING ---
// Fetch all orders belonging to the current user.
$orders_stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$orders_stmt->execute([$user_id]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// STEP 3: Now, include the header.
include 'includes/header.php';
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --warning-gradient: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
        --danger-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
        --card-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        --card-hover-shadow: 0 30px 60px rgba(0, 0, 0, 0.15);
        --border-radius: 20px;
        --glass-bg: rgba(255, 255, 255, 0.25);
        --glass-border: rgba(255, 255, 255, 0.18);
    }

    .page-header {
        background: var(--primary-gradient);
        padding: 80px 0;
        margin-bottom: 60px;
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
        animation: slide 20s infinite linear;
    }

    @keyframes slide {
        0% {
            transform: translateX(0);
        }

        100% {
            transform: translateX(20px);
        }
    }

    .page-title {
        font-size: 3rem;
        font-weight: 900;
        color: white;
        text-align: center;
        margin: 0;
        text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        position: relative;
        z-index: 2;
    }

    .page-subtitle {
        font-size: 1.2rem;
        color: rgba(255, 255, 255, 0.9);
        text-align: center;
        margin-top: 1rem;
        position: relative;
        z-index: 2;
    }

    .orders-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .modern-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--card-shadow);
        border: none;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        position: relative;
    }

    .modern-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--card-hover-shadow);
    }

    .card-header-modern {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-bottom: none;
        padding: 2rem;
        position: relative;
    }

    .card-header-modern h3 {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0;
        color: #2d3748;
        display: flex;
        align-items: center;
    }

    .card-header-modern .icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: var(--primary-gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
    }

    .card-header-modern .icon i {
        color: white;
        font-size: 1.5rem;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        position: relative;
    }

    .empty-state-icon {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e0 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 2rem;
    }

    .empty-state-icon i {
        font-size: 3rem;
        color: #718096;
    }

    .empty-state h4 {
        font-size: 1.5rem;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 1rem;
    }

    .empty-state p {
        color: #718096;
        font-size: 1.1rem;
        margin-bottom: 2rem;
    }

    .btn-modern {
        background: var(--primary-gradient);
        border: none;
        border-radius: 12px;
        padding: 12px 30px;
        font-weight: 600;
        color: white;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .orders-table {
        margin: 0;
    }

    .table-modern {
        margin: 0;
        border: none;
    }

    .table-modern thead {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    }

    .table-modern thead th {
        border: none;
        padding: 1.5rem 1.5rem;
        font-weight: 700;
        color: #4a5568;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
    }

    .table-modern tbody tr {
        border: none;
        transition: all 0.3s ease;
        position: relative;
    }

    .table-modern tbody tr:hover {
        background: rgba(102, 126, 234, 0.05);
        transform: translateX(5px);
    }

    /* .table-modern tbody tr::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--primary-gradient);
        opacity: 0;
        transition: opacity 0.3s ease;
    } */

    .table-modern tbody tr:hover::before {
        opacity: 1;
    }

    .table-modern td {
        padding: 1.5rem 1.5rem;
        border: none;
        vertical-align: middle;
        font-size: 0.95rem;
    }

    .order-id {
        font-weight: 700;
        color: #4a5568;
        font-family: 'Courier New', monospace;
        background: rgba(102, 126, 234, 0.1);
        padding: 0.25rem 0.75rem;
        border-radius: 6px;
        display: inline-block;
    }

    .order-date {
        color: #718096;
        font-weight: 500;
    }

    .order-total {
        font-weight: 700;
        color: #2d3748;
        font-size: 1.1rem;
    }

    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
        position: relative;
        overflow: hidden;
    }

    .status-completed {
        background: var(--success-gradient);
        color: white;
    }

    .status-pending {
        background: var(--warning-gradient);
        color: #744210;
    }

    .status-cancelled {
        background: var(--danger-gradient);
        color: white;
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
        align-items: center;
    }

    .btn-action {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        border: 2px solid;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }

    .btn-view {
        background: white;
        color: #667eea;
        border-color: #667eea;
    }

    .btn-view:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }

    .btn-cancel {
        background: white;
        color: #e53e3e;
        border-color: #e53e3e;
    }

    .btn-cancel:hover {
        background: #e53e3e;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(229, 62, 62, 0.3);
    }

    .loading-state {
        opacity: 0.6;
        pointer-events: none;
        position: relative;
    }

    .loading-state::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 20px;
        height: 20px;
        margin: -10px;
        border: 2px solid #667eea;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 1s infinite linear;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    .floating-elements {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: -1;
    }

    .floating-elements::before,
    .floating-elements::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        background: rgba(102, 126, 234, 0.05);
        animation: float-slow 20s infinite ease-in-out;
    }

    .floating-elements::before {
        width: 300px;
        height: 300px;
        top: 20%;
        left: -150px;
        animation-delay: 0s;
    }

    .floating-elements::after {
        width: 200px;
        height: 200px;
        bottom: 20%;
        right: -100px;
        animation-delay: 10s;
        background: rgba(240, 147, 251, 0.05);
    }

    @keyframes float-slow {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
        }

        50% {
            transform: translateY(-50px) rotate(180deg);
        }
    }

    @media (max-width: 768px) {
        .page-title {
            font-size: 2.2rem;
        }

        .page-subtitle {
            font-size: 1rem;
        }

        .table-responsive {
            border-radius: var(--border-radius);
            overflow-x: auto;
        }

        .table-modern td,
        .table-modern th {
            padding: 1rem;
            font-size: 0.9rem;
        }

        .action-buttons {
            flex-direction: column;
            gap: 0.3rem;
        }

        .btn-action {
            width: 100%;
            justify-content: center;
        }

        .order-id {
            font-size: 0.8rem;
        }

        .card-header-modern {
            padding: 1.5rem;
        }
    }
</style>

<div class="floating-elements"></div>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1 class="page-title">Order History</h1>
        <p class="page-subtitle">Track and manage all your orders in one place</p>
    </div>
</section>

<main class="orders-container">
    <div class="modern-card mb-5">
        <div class="card-header-modern">
            <h3>
                <div class="icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                Your Orders
            </h3>
        </div>
        <div class="card-body p-0">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-cart-x"></i>
                    </div>
                    <h4>No Orders Yet</h4>
                    <p>You haven't placed any orders yet. Start shopping to see your order history here.</p>
                    <a href="index" class="btn-modern">
                        <i class="bi bi-shop me-2"></i>
                        Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="orders-table" class="table table-modern">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date Placed</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr id="order-row-<?= $order['id'] ?>">
                                    <td>
                                        <span class="order-id">#<?= esc_html($order['id']) ?></span>
                                    </td>
                                    <td>
                                        <span class="order-date"><?= format_date($order['created_at']) ?></span>
                                    </td>
                                    <td>
                                        <span class="order-total"><?= formatPrice($order['total_amount']) ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge 
                                        <?php
                                        switch (strtolower($order['status'])) {
                                            case 'completed':
                                                echo 'status-completed';
                                                break;
                                            case 'pending':
                                                echo 'status-pending';
                                                break;
                                            case 'cancelled':
                                                echo 'status-cancelled';
                                                break;
                                            default:
                                                echo 'status-pending';
                                        }
                                        ?>">
                                            <?= esc_html($order['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="order-details.php?id=<?= $order['id'] ?>" class="btn-action btn-view">
                                                <i class="bi bi-eye-fill"></i>
                                                View Details
                                            </a>
                                            <?php if (strtolower($order['status']) === 'pending'): ?>
                                                <button class="btn-action btn-cancel cancel-order-btn"
                                                    data-order-id="<?= $order['id'] ?>">
                                                    <i class="bi bi-x-circle"></i>
                                                    Cancel
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ordersTable = document.getElementById('orders-table');

        if (ordersTable) {
            ordersTable.addEventListener('click', function(e) {
                if (e.target.closest('.cancel-order-btn')) {
                    const button = e.target.closest('.cancel-order-btn');
                    const orderId = button.dataset.orderId;
                    const row = document.getElementById(`order-row-${orderId}`);

                    if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
                        // Add loading state
                        button.classList.add('loading-state');
                        button.disabled = true;

                        fetch('cancel_order.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'order_id=' + encodeURIComponent(orderId)
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    // Animate the status change
                                    const statusBadge = row.querySelector('.status-badge');
                                    const actionButtons = row.querySelector('.action-buttons');

                                    // Update badge with animation
                                    statusBadge.style.transform = 'scale(0)';
                                    setTimeout(() => {
                                        statusBadge.textContent = 'Cancelled';
                                        statusBadge.className = 'status-badge status-cancelled';
                                        statusBadge.style.transform = 'scale(1)';
                                    }, 200);

                                    // Remove the cancel button with fade effect
                                    button.style.opacity = '0';
                                    setTimeout(() => {
                                        button.remove();
                                    }, 300);

                                    // Show success message
                                    showNotification('Order cancelled successfully!', 'success');
                                } else {
                                    button.classList.remove('loading-state');
                                    button.disabled = false;
                                    showNotification('Error: ' + data.message, 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                button.classList.remove('loading-state');
                                button.disabled = false;
                                showNotification('An unexpected error occurred. Please try again.', 'error');
                            });
                    }
                }
            });
        }

        // Notification system
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
            <div class="notification-content">
                <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'}"></i>
                <span>${message}</span>
            </div>
        `;

            // Add notification styles if not already present
            if (!document.querySelector('#notification-styles')) {
                const styles = document.createElement('style');
                styles.id = 'notification-styles';
                styles.textContent = `
                .notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 1rem 1.5rem;
                    border-radius: 12px;
                    color: white;
                    font-weight: 600;
                    z-index: 10000;
                    transform: translateX(400px);
                    transition: transform 0.3s ease;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                }
                .notification-success {
                    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                }
                .notification-error {
                    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
                }
                .notification-content {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                }
                .notification.show {
                    transform: translateX(0);
                }
            `;
                document.head.appendChild(styles);
            }

            document.body.appendChild(notification);

            // Trigger animation
            setTimeout(() => notification.classList.add('show'), 100);

            // Remove after 4 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
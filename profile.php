<?php
// This is the customer profile page, e.g., profile

// STEP 1: Start the session and include necessary files FIRST.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';

// STEP 2: Authentication Check. Redirect if not logged in.
if (!isLoggedIn()) {
    redirect('login?redirect=profile');
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_messages = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Validate CSRF token
        if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            $errors[] = "Your session expired. Please try again.";
        } else {
            // Profile update logic
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            // Validation
            if (empty($username)) {
                $errors[] = "Username is required.";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "A valid email address is required.";
            }
            if (empty($phone)) {
                $errors[] = "Phone number is required.";
            }
            if (empty($address)) {
                $errors[] = "Address is required.";
            }

            // Check if email already exists for another user
            if (empty($errors)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetch()) {
                    $errors[] = "This email address is already registered to another account.";
                }
            }

            if (empty($errors)) {
                try {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                    if ($stmt->execute([$username, $email, $phone, $address, $user_id])) {
                        $_SESSION['user_name'] = $username; // Update session
                        $success_messages[] = "Profile updated successfully!";

                        // Store success message in session for redirect
                        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Profile updated successfully!'];
                        redirect('profile');
                    } else {
                        $errors[] = "Failed to update profile. Please try again.";
                    }
                } catch (PDOException $e) {
                    $errors[] = "A database error occurred. Please try again later.";
                }
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Validate CSRF token
        if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            $errors[] = "Your session expired. Please try again.";
        } else {
            // Password change logic
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // Validation
            if (empty($current_password)) {
                $errors[] = "Current password is required.";
            }
            if (strlen($new_password) < 8) {
                $errors[] = "New password must be at least 8 characters long.";
            }
            if ($new_password !== $confirm_password) {
                $errors[] = "New passwords do not match.";
            }

            if (empty($errors)) {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_data_for_password = $stmt->fetch();

                if (!$user_data_for_password || !password_verify($current_password, $user_data_for_password['password'])) {
                    $errors[] = "Current password is incorrect.";
                } else {
                    // Update password
                    try {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        if ($stmt->execute([$hashed_password, $user_id])) {
                            $success_messages[] = "Password changed successfully!";

                            // Store success message in session for redirect
                            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Password changed successfully!'];
                            redirect('profile');
                        } else {
                            $errors[] = "Failed to change password. Please try again.";
                        }
                    } catch (PDOException $e) {
                        $errors[] = "A database error occurred. Please try again later.";
                    }
                }
            }
        }
    }
}

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // User doesn't exist anymore
    redirect('logout');
}

// Fetch user's recent orders for the dashboard
$orders_stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$orders_stmt->execute([$user_id]);
$recent_orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total orders
$total_orders_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
$total_orders_stmt->execute([$user_id]);
$total_orders = $total_orders_stmt->fetch()['total'];

// Handle flash messages
$flash_message = $_SESSION['flash_message'] ?? null;
if ($flash_message) {
    if ($flash_message['type'] === 'success') {
        $success_messages[] = $flash_message['message'];
    } else {
        $errors[] = $flash_message['message'];
    }
    unset($_SESSION['flash_message']);
}

// STEP 3: Include the header 
include 'includes/header.php';
?>






<main class="profile-container">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-md-auto">
                <div class="profile-avatar">
                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                </div>
            </div>
            <div class="col-md">
                <div class="profile-info">
                    <h1><?= esc_html($user['username']) ?></h1>
                    <div class="profile-meta">
                        <i class="bi bi-envelope me-2"></i><?= esc_html($user['email']) ?>
                        <span class="mx-3">•</span>
                        <i class="bi bi-calendar me-2"></i>Member since <?= format_date($user['created_at'], 'M Y') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert-modern alert-danger">
            <h6 class="mb-2"><i class="bi bi-exclamation-triangle me-2"></i>Please fix the following errors:</h6>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= esc_html($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_messages)): ?>
        <?php foreach ($success_messages as $message): ?>
            <div class="alert-modern alert-success">
                <i class="bi bi-check-circle me-2"></i><?= esc_html($message) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="stat-number"><?= $total_orders ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-heart"></i>
            </div>
            <div class="stat-number">0</div>
            <div class="stat-label">Wishlist Items</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-star"></i>
            </div>
            <div class="stat-number">0</div>
            <div class="stat-label">Reviews</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="bi bi-shield-check"></i>
            </div>
            <div class="stat-number">Verified</div>
            <div class="stat-label">Account Status</div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="profile-tabs">
        <button class="tab-btn active" data-tab="profile">
            <i class="bi bi-person-circle"></i>
            <span>Profile Information</span>
        </button>
        <button class="tab-btn" data-tab="password">
            <i class="bi bi-shield-lock"></i>
            <span>Change Password</span>
        </button>
        <button class="tab-btn" data-tab="orders">
            <i class="bi bi-clock-history"></i>
            <span>Recent Orders</span>
            <?php if (!empty($recent_orders)): ?>
                <span class="tab-badge"><?= count($recent_orders) ?></span>
            <?php endif; ?>
        </button>
    </div>

    <!-- Tab Content Container -->
    <div class="tab-content-container">
        <!-- Profile Information Tab -->
        <div class="tab-content active" id="profile-tab">
            <div class="modern-card">
                <div class="card-header-modern">
                    <h3>
                        <div class="icon">
                            <i class="bi bi-person"></i>
                        </div>
                        Profile Information
                    </h3>
                </div>
                <div class="card-body-modern">
                    <form method="post" id="profileForm">
                        <?= csrf_input() ?>
                        <input type="hidden" name="update_profile" value="1">

                        <div class="form-group-modern">
                            <label for="username" class="form-label-modern">Full Name</label>
                            <input type="text" id="username" name="username" class="form-control-modern"
                                value="<?= esc_html($user['username']) ?>" required>
                        </div>

                        <div class="form-group-modern">
                            <label for="email" class="form-label-modern">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control-modern"
                                value="<?= esc_html($user['email']) ?>" required>
                        </div>

                        <div class="form-group-modern">
                            <label for="phone" class="form-label-modern">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control-modern"
                                value="<?= esc_html($user['phone']) ?>" required>
                        </div>

                        <div class="form-group-modern">
                            <label for="address" class="form-label-modern">Full Address</label>
                            <textarea id="address" name="address" class="form-control-modern" rows="3" required><?= esc_html($user['address']) ?></textarea>
                        </div>

                        <button type="submit" class="btn-modern" id="profileSubmitBtn">
                            <i class="bi bi-check-lg"></i>
                            Update Profile
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Change Password Tab -->
        <div class="tab-content" id="password-tab">
            <div class="modern-card">
                <div class="card-header-modern">
                    <h3>
                        <div class="icon">
                            <i class="bi bi-lock"></i>
                        </div>
                        Change Password
                    </h3>
                </div>
                <div class="card-body-modern">
                    <form method="post" id="passwordForm">
                        <?= csrf_input() ?>
                        <input type="hidden" name="change_password" value="1">

                        <div class="form-group-modern">
                            <label for="current_password" class="form-label-modern">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control-modern" required>
                        </div>

                        <div class="form-group-modern">
                            <label for="new_password" class="form-label-modern">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control-modern" required>
                            <small class="text-muted">Must be at least 8 characters long</small>
                        </div>

                        <div class="form-group-modern">
                            <label for="confirm_password" class="form-label-modern">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control-modern" required>
                        </div>

                        <button type="submit" class="btn-modern btn-danger-modern" id="passwordSubmitBtn">
                            <i class="bi bi-shield-lock"></i>
                            Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Orders Tab -->
        <div class="tab-content" id="orders-tab">
            <div class="modern-card">
                <div class="card-header-modern">
                    <div class="d-flex justify-content-between align-items-center w-100">
                        <h3>
                            <div class="icon">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            Recent Orders
                        </h3>
                        <?php if (!empty($recent_orders)): ?>
                            <span class="badge bg-primary"><?= count($recent_orders) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body-modern">
                    <?php if (empty($recent_orders)): ?>
                        <div class="empty-state-orders">
                            <div class="empty-icon">
                                <i class="bi bi-cart-x"></i>
                            </div>
                            <h5>No Orders Yet</h5>
                            <p class="text-muted mb-3">You haven't placed any orders yet. Start exploring our products!</p>
                            <a href="index" class="btn-modern">
                                <i class="bi bi-shop me-2"></i>
                                Start Shopping
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="orders-timeline">
                            <?php foreach ($recent_orders as $index => $order): ?>
                                <div class="order-card <?= $index === 0 ? 'latest' : '' ?>">
                                    <div class="order-header">
                                        <div class="order-main-info">
                                            <div class="order-id-badge">
                                                <i class="bi bi-receipt me-2"></i>
                                                Order #<?= esc_html($order['id']) ?>
                                            </div>
                                            <div class="order-date-time">
                                                <i class="bi bi-calendar3 me-1"></i>
                                                <?= format_date($order['created_at'], 'M d, Y') ?>
                                                <span class="time-ago">• <?= format_date($order['created_at'], 'g:i A') ?></span>
                                            </div>
                                        </div>
                                        <div class="order-actions">
                                            <span class="status-badge-modern status-<?= strtolower($order['status']) ?>">
                                                <?php
                                                $status_icons = [
                                                    'completed' => 'check-circle-fill',
                                                    'pending' => 'clock-fill',
                                                    'cancelled' => 'x-circle-fill'
                                                ];
                                                $status = strtolower($order['status']);
                                                ?>
                                                <i class="bi bi-<?= $status_icons[$status] ?? 'clock-fill' ?> me-1"></i>
                                                <?= esc_html(ucfirst($order['status'])) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="order-body">
                                        <div class="order-details-grid">
                                            <div class="detail-item">
                                                <span class="detail-label">Total Amount</span>
                                                <span class="detail-value price-highlight"><?= formatPrice($order['total_amount']) ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="detail-label">Payment Method</span>
                                                <span class="detail-value">
                                                    <i class="bi bi-credit-card me-1"></i>
                                                    <?= esc_html($order['payment_method'] ?? 'Cash on Delivery') ?>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="order-footer">
                                            <div class="order-progress">
                                                <?php
                                                $progress = match (strtolower($order['status'])) {
                                                    'pending' => 25,
                                                    'processing' => 50,
                                                    'shipped' => 75,
                                                    'completed' => 100,
                                                    'cancelled' => 0,
                                                    default => 25
                                                };
                                                $progress_color = match (strtolower($order['status'])) {
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger',
                                                    default => 'primary'
                                                };
                                                ?>
                                                <div class="progress-bar-container">
                                                    <div class="progress-bar bg-<?= $progress_color ?>" style="width: <?= $progress ?>%"></div>
                                                </div>
                                                <small class="progress-text">
                                                    <?= strtolower($order['status']) === 'cancelled' ? 'Order Cancelled' : $progress . '% Complete' ?>
                                                </small>
                                            </div>

                                            <div class="quick-actions">
                                                <a href="order-details?id=<?= $order['id'] ?>" class="btn-action-sm btn-view">
                                                    <i class="bi bi-eye"></i>
                                                    <span>View</span>
                                                </a>

                                                <?php if (strtolower($order['status']) === 'pending'): ?>
                                                    <button class="btn-action-sm btn-cancel" onclick="cancelOrder(<?= $order['id'] ?>)">
                                                        <i class="bi bi-x-circle"></i>
                                                        <span>Cancel</span>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="orders-footer">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="orders-summary">
                                        <small class="text-muted">
                                            Showing <?= count($recent_orders) ?> of <?= $total_orders ?> total orders
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <a href="orders" class="btn-modern">
                                        <i class="bi bi-list-ul me-2"></i>
                                        View All Orders
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching functionality
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');

                // Remove active class from all tabs and content
                tabBtns.forEach(b => b.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                document.getElementById(targetTab + '-tab').classList.add('active');

                // Save the active tab to localStorage for persistence
                localStorage.setItem('activeProfileTab', targetTab);
            });
        });

        // Restore the active tab from localStorage
        const savedTab = localStorage.getItem('activeProfileTab');
        if (savedTab) {
            const savedTabBtn = document.querySelector(`[data-tab="${savedTab}"]`);
            const savedTabContent = document.getElementById(savedTab + '-tab');

            if (savedTabBtn && savedTabContent) {
                // Remove active from all
                tabBtns.forEach(b => b.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                // Activate saved tab
                savedTabBtn.classList.add('active');
                savedTabContent.classList.add('active');
            }
        }

        // Profile form submission
        const profileForm = document.getElementById('profileForm');
        const profileSubmitBtn = document.getElementById('profileSubmitBtn');

        if (profileForm && profileSubmitBtn) {
            profileForm.addEventListener('submit', function(e) {
                profileSubmitBtn.disabled = true;
                profileSubmitBtn.innerHTML = '<div class="spinner"></div>Updating...';

                // Fallback to re-enable button after 10 seconds
                setTimeout(() => {
                    if (profileSubmitBtn.disabled) {
                        profileSubmitBtn.disabled = false;
                        profileSubmitBtn.innerHTML = '<i class="bi bi-check-lg"></i>Update Profile';
                    }
                }, 10000);
            });
        }

        // Password form submission
        const passwordForm = document.getElementById('passwordForm');
        const passwordSubmitBtn = document.getElementById('passwordSubmitBtn');

        if (passwordForm && passwordSubmitBtn) {
            passwordForm.addEventListener('submit', function(e) {
                passwordSubmitBtn.disabled = true;
                passwordSubmitBtn.innerHTML = '<div class="spinner"></div>Changing...';

                // Fallback to re-enable button after 10 seconds
                setTimeout(() => {
                    if (passwordSubmitBtn.disabled) {
                        passwordSubmitBtn.disabled = false;
                        passwordSubmitBtn.innerHTML = '<i class="bi bi-shield-lock"></i>Change Password';
                    }
                }, 10000);
            });
        }

        // Password confirmation validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');

        if (newPassword && confirmPassword) {
            function validatePasswords() {
                if (confirmPassword.value && newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }

            newPassword.addEventListener('input', validatePasswords);
            confirmPassword.addEventListener('input', validatePasswords);
        }
    });

    // Order management functions
    function reorderItems(orderId) {
        if (confirm('Add all items from this order to your cart?')) {
            fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `reorder_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Items added to cart successfully!', 'success');
                        // Update cart badge if present
                        const cartBadge = document.querySelector('.cart-badge');
                        if (cartBadge && data.cart_count) {
                            cartBadge.textContent = data.cart_count;
                        }
                    } else {
                        showNotification('Error: ' + (data.message || 'Could not add items to cart'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred. Please try again.', 'error');
                });
        }
    }

    function cancelOrder(orderId) {
        if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
            const orderCard = document.querySelector(`[data-order-id="${orderId}"]`) ||
                document.querySelector(`.order-card:has(.order-id-badge:contains("#${orderId}"))`);

            fetch('cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success || data.status === 'success') {
                        showNotification('Order cancelled successfully!', 'success');

                        // Update the order card UI
                        if (orderCard) {
                            // Update status badge
                            const statusBadge = orderCard.querySelector('.status-badge-modern');
                            if (statusBadge) {
                                statusBadge.className = 'status-badge-modern status-cancelled';
                                statusBadge.innerHTML = '<i class="bi bi-x-circle-fill me-1"></i>Cancelled';
                            }

                            // Update progress bar
                            const progressBar = orderCard.querySelector('.progress-bar');
                            const progressText = orderCard.querySelector('.progress-text');
                            if (progressBar && progressText) {
                                progressBar.style.width = '0%';
                                progressBar.className = 'progress-bar bg-danger';
                                progressText.textContent = 'Order Cancelled';
                            }

                            // Remove cancel button
                            const cancelBtn = orderCard.querySelector('.btn-cancel');
                            if (cancelBtn) {
                                cancelBtn.remove();
                            }
                        }

                        // Reload page after a short delay to reflect changes
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showNotification('Error: ' + (data.message || 'Could not cancel order'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred. Please try again.', 'error');
                });
        }
    }

    function showNotification(message, type) {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notification => notification.remove());

        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
        <div class="notification-content">
            <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                <i class="bi bi-x"></i>
            </button>
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
                font-weight: 500;
                z-index: 10000;
                transform: translateX(400px);
                transition: transform 0.3s ease;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                max-width: 400px;
                min-width: 300px;
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
                gap: 0.75rem;
                position: relative;
            }
            .notification-close {
                background: none;
                border: none;
                color: white;
                cursor: pointer;
                padding: 0;
                margin-left: auto;
                font-size: 1.2rem;
                opacity: 0.8;
            }
            .notification-close:hover {
                opacity: 1;
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

        // Remove after 5 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
</script>

<?php include 'includes/footer.php'; ?>
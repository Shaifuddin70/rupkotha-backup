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






<main class="profile-page-minimal">
    <div class="container">
        <!-- Minimal Profile Header -->
        <div class="profile-header-minimal">
            <div class="profile-avatar-minimal">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
            <div class="profile-info-minimal">
                <h1 class="profile-name-minimal"><?= esc_html($user['username']) ?></h1>
                <p class="profile-email-minimal"><?= esc_html($user['email']) ?></p>
                <p class="profile-meta-minimal">Member since <?= format_date($user['created_at'], 'F Y') ?></p>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (!empty($errors)): ?>
            <div class="alert-minimal alert-error">
                <i class="bi bi-exclamation-circle"></i>
                <div>
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= esc_html($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_messages)): ?>
            <?php foreach ($success_messages as $message): ?>
                <div class="alert-minimal alert-success">
                    <i class="bi bi-check-circle"></i>
                    <span><?= esc_html($message) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="stats-minimal">
            <div class="stat-minimal">
                <div class="stat-value-minimal"><?= $total_orders ?></div>
                <div class="stat-label-minimal">Orders</div>
            </div>
            <div class="stat-minimal">
                <div class="stat-value-minimal">0</div>
                <div class="stat-label-minimal">Wishlist</div>
            </div>
            <div class="stat-minimal">
                <div class="stat-value-minimal">0</div>
                <div class="stat-label-minimal">Reviews</div>
            </div>
            <div class="stat-minimal">
                <div class="stat-value-minimal">Verified</div>
                <div class="stat-label-minimal">Status</div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tabs-minimal">
            <button class="tab-minimal active" data-tab="profile">
                <i class="bi bi-person"></i>
                <span>Profile</span>
            </button>
            <button class="tab-minimal" data-tab="password">
                <i class="bi bi-lock"></i>
                <span>Password</span>
            </button>
            <button class="tab-minimal" data-tab="orders">
                <i class="bi bi-clock-history"></i>
                <span>Orders</span>
                <?php if (!empty($recent_orders)): ?>
                    <span class="tab-badge-minimal"><?= count($recent_orders) ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- Tab Content -->
        <div class="tab-content-wrapper-minimal">
            <!-- Profile Information Tab -->
            <div class="tab-content-minimal active" id="profile-tab">
                <div class="card-minimal">
                    <h2 class="card-title-minimal">Profile Information</h2>
                    <form method="post" id="profileForm" class="form-minimal">
                        <?= csrf_input() ?>
                        <input type="hidden" name="update_profile" value="1">

                        <div class="form-field-minimal">
                            <label for="username">Full Name</label>
                            <input type="text" id="username" name="username" 
                                value="<?= esc_html($user['username']) ?>" required>
                        </div>

                        <div class="form-field-minimal">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" 
                                value="<?= esc_html($user['email']) ?>" required>
                        </div>

                        <div class="form-field-minimal">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" 
                                value="<?= esc_html($user['phone']) ?>" required>
                        </div>

                        <div class="form-field-minimal">
                            <label for="address">Full Address</label>
                            <textarea id="address" name="address" rows="3" required><?= esc_html($user['address']) ?></textarea>
                        </div>

                        <button type="submit" class="btn-primary-minimal" id="profileSubmitBtn">
                            Update Profile
                        </button>
                    </form>
                </div>
            </div>

            <!-- Change Password Tab -->
            <div class="tab-content-minimal" id="password-tab">
                <div class="card-minimal">
                    <h2 class="card-title-minimal">Change Password</h2>
                    <form method="post" id="passwordForm" class="form-minimal">
                        <?= csrf_input() ?>
                        <input type="hidden" name="change_password" value="1">

                        <div class="form-field-minimal">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-field-minimal">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                            <small>Must be at least 8 characters long</small>
                        </div>

                        <div class="form-field-minimal">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>

                        <button type="submit" class="btn-primary-minimal" id="passwordSubmitBtn">
                            Change Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Recent Orders Tab -->
            <div class="tab-content-minimal" id="orders-tab">
                <div class="card-minimal">
                    <div class="card-header-minimal">
                        <h2 class="card-title-minimal">Recent Orders</h2>
                        <?php if (!empty($recent_orders)): ?>
                            <span class="badge-minimal"><?= count($recent_orders) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($recent_orders)): ?>
                        <div class="empty-state-minimal">
                            <i class="bi bi-cart-x"></i>
                            <h3>No Orders Yet</h3>
                            <p>You haven't placed any orders yet. Start exploring our products!</p>
                            <a href="index" class="btn-primary-minimal">
                                Start Shopping
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="orders-list-minimal">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="order-item-minimal">
                                    <div class="order-header-minimal">
                                        <div>
                                            <div class="order-id-minimal">Order #<?= esc_html($order['id']) ?></div>
                                            <div class="order-date-minimal"><?= format_date($order['created_at'], 'M d, Y') ?></div>
                                        </div>
                                        <span class="status-minimal status-<?= strtolower($order['status']) ?>">
                                            <?= esc_html(ucfirst($order['status'])) ?>
                                        </span>
                                    </div>
                                    <div class="order-body-minimal">
                                        <div class="order-details-minimal">
                                            <div>
                                                <span class="detail-label-minimal">Total</span>
                                                <span class="detail-value-minimal"><?= formatPrice($order['total_amount']) ?></span>
                                            </div>
                                            <div>
                                                <span class="detail-label-minimal">Payment</span>
                                                <span class="detail-value-minimal"><?= esc_html($order['payment_method'] ?? 'Cash on Delivery') ?></span>
                                            </div>
                                        </div>
                                        <div class="order-actions-minimal">
                                            <a href="order-details?id=<?= $order['id'] ?>" class="btn-link-minimal">
                                                View Details
                                            </a>
                                            <?php if (strtolower($order['status']) === 'pending'): ?>
                                                <button class="btn-link-minimal btn-danger-link" onclick="cancelOrder(<?= $order['id'] ?>)">
                                                    Cancel
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="orders-footer-minimal">
                            <p>Showing <?= count($recent_orders) ?> of <?= $total_orders ?> orders</p>
                            <a href="orders" class="btn-link-minimal">
                                View All Orders →
                            </a>
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
        const tabBtns = document.querySelectorAll('.tab-minimal');
        const tabContents = document.querySelectorAll('.tab-content-minimal');

        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');

                // Remove active class from all tabs and content
                tabBtns.forEach(b => b.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                const targetContent = document.getElementById(targetTab + '-tab');
                if (targetContent) {
                    targetContent.classList.add('active');
                }

                // Save the active tab to localStorage for persistence
                localStorage.setItem('activeProfileTab', targetTab);
            });
        });

        // Restore the active tab from localStorage
        const savedTab = localStorage.getItem('activeProfileTab');
        if (savedTab) {
            const savedTabBtn = document.querySelector(`.tab-minimal[data-tab="${savedTab}"]`);
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
<?php
// This is the customer profile page, e.g., profile

// STEP 1: Start session and include necessary files FIRST.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';

// STEP 2: Authentication Check. Redirect if not logged in.
if (!isLoggedIn()) {
    // We add a redirect parameter so they come back here after logging in.
    redirect('login?redirect=profile');
}

$user_id = $_SESSION['user_id'];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ------------------ PROFILE UPDATE ------------------ */
    if (isset($_POST['update_profile'])) {
        $errors = [];

        // CSRF validation
        if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
            $errors[] = 'Your session expired. Please refresh the page and try again.';
        }

        // If CSRF failed, skip rest of processing
        if (!empty($errors)) {
            generate_csrf_token();
        } else {
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $phone    = trim($_POST['phone'] ?? '');
            $address  = trim($_POST['address'] ?? '');

            // Validation
            if (empty($username) || empty($email) || empty($phone) || empty($address)) {
                $errors[] = "All profile fields are required.";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Please enter a valid email address.";
            }

            // Check for duplicate email
            if (empty($errors)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetch()) {
                    $errors[] = "This email address is already in use by another account.";
                }
            }

            // Update profile if no errors
            if (empty($errors)) {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                if ($stmt->execute([$username, $email, $phone, $address, $user_id])) {
                    $_SESSION['user_name'] = $username;
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Profile updated successfully!'];
                    redirect('profile');
                } else {
                    $errors[] = 'Failed to update profile. Please contact support.';
                }
            }
        }
    }

    /* ------------------ PASSWORD CHANGE ------------------ */
    if (isset($_POST['change_password'])) {
        $errors = [];

        // CSRF validation
        if (!validate_csrf_token($_POST['csrf_token'] ?? null)) {
            $errors[] = 'Your session expired. Please refresh the page and try again.';
        }

        // If CSRF failed, skip rest of processing
        if (!empty($errors)) {
            generate_csrf_token();
        } else {
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_new_password = $_POST['confirm_new_password'] ?? '';

            // Fetch current user's hashed password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_data_for_password = $stmt->fetch();

            if (!$user_data_for_password || !password_verify($current_password, $user_data_for_password['password'])) {
                $errors[] = "Your current password is not correct.";
            }
            if (strlen($new_password) < 8) {
                $errors[] = "New password must be at least 8 characters long.";
            }
            if ($new_password !== $confirm_new_password) {
                $errors[] = "New passwords do not match.";
            }

            // Update password if no errors
            if (empty($errors)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed_password, $user_id])) {
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Password changed successfully!'];
                    redirect('profile');
                } else {
                    $errors[] = 'Failed to change password. Please contact support.';
                }
            }
        }
    }
}


// STEP 4: Fetch data for page display AFTER processing forms.
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user is not found (e.g., deleted after login), handle it gracefully
if (!$user) {
    redirect('logout');
}

// Fetch user's order history
$orders_stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$orders_stmt->execute([$user_id]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// STEP 5: Now, include the header.
include 'includes/header.php';
?>



<div class="profile-container">
    <div class="container">

        <?php
        // *** ADDED: Display and clear the flash message ***
        if (isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            $alert_class = $flash['type'] === 'success' ? 'alert-success' : 'alert-danger';
            // Using Bootstrap alert classes for demonstration, but our custom styles will override
            echo "<div class='custom-alert " . ($flash['type'] === 'success' ? 'alert-success' : 'alert-danger') . "'>
                    <i class='bi " . ($flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill') . " me-2'></i>
                    " . esc_html($flash['message']) . "
                  </div>";
        }
        ?>

        <div class="profile-header fade-in">
            <div class="profile-avatar">
                <i class="bi bi-person-fill"></i>
            </div>
            <h2 class="mb-1"><?= esc_html($user['username']) ?></h2>
            <p class="text-muted mb-0"><?= esc_html($user['email']) ?></p>
        </div>

        <div class="row">
            <div class="col-lg-3 col-md-4">
                <div class="custom-sidebar fade-in">
                    <div class="nav flex-column custom-nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <button class="nav-link active" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab" aria-controls="v-pills-profile" aria-selected="true">
                            <i class="bi bi-person-fill"></i>Profile Details
                        </button>
                        <button class="nav-link" id="v-pills-password-tab" data-bs-toggle="pill" data-bs-target="#v-pills-password" type="button" role="tab" aria-controls="v-pills-password" aria-selected="false">
                            <i class="bi bi-key-fill"></i>Change Password
                        </button>
                        <button class="nav-link" id="v-pills-orders-tab" data-bs-toggle="pill" data-bs-target="#v-pills-orders" type="button" role="tab" aria-controls="v-pills-orders" aria-selected="false">
                            <i class="bi bi-box-seam-fill"></i>Order History
                        </button>
                        <a class="nav-link" href="logout" style="color: var(--danger-color);">
                            <i class="bi bi-box-arrow-right"></i>Logout
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-9 col-md-8">
                <div class="tab-content fade-in" id="v-pills-tabContent">

                    <?php if (!empty($errors)): ?>
                        <div class="custom-alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2" style="list-style-type: none; padding-left: 0;">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= esc_html($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel" aria-labelledby="v-pills-profile-tab">
                        <div class="custom-card">
                            <div class="custom-card-header">
                                <h5><i class="bi bi-person-badge me-2"></i>My Profile Details</h5>
                            </div>
                            <div class="card-body p-4">
                                <form action="profile.php" method="post">
                                    <?= csrf_input(); ?>
                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <label for="username" class="custom-form-label">Full Name</label>
                                            <input type="text" name="username" id="username" class="form-control custom-form-control" value="<?= esc_html($user['username']) ?>" required>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <label for="email" class="custom-form-label">Email Address</label>
                                            <input type="email" name="email" id="email" class="form-control custom-form-control" value="<?= esc_html($user['email']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label for="phone" class="custom-form-label">Phone Number</label>
                                        <input type="tel" name="phone" id="phone" class="form-control custom-form-control" value="<?= esc_html($user['phone']) ?>" required>
                                    </div>
                                    <div class="mb-4">
                                        <label for="address" class="custom-form-label">Full Address</label>
                                        <textarea name="address" id="address" class="form-control custom-form-control" rows="4" required><?= esc_html($user['address']) ?></textarea>
                                    </div>
                                    <button type="submit" name="update_profile" class="custom-btn">
                                        <i class="bi bi-check-circle me-2"></i>Save Changes
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="v-pills-password" role="tabpanel" aria-labelledby="v-pills-password-tab">
                        <div class="custom-card">
                            <div class="custom-card-header">
                                <h5><i class="bi bi-shield-lock me-2"></i>Change Password</h5>
                            </div>
                            <div class="card-body p-4">
                                <form action="profile.php" method="post">
                                    <?= csrf_input(); ?>
                                    <div class="mb-4">
                                        <label for="current_password" class="custom-form-label">Current Password</label>
                                        <input type="password" name="current_password" id="current_password" class="form-control custom-form-control" required>
                                    </div>
                                    <div class="mb-4">
                                        <label for="new_password" class="custom-form-label">New Password</label>
                                        <input type="password" name="new_password" id="new_password" class="form-control custom-form-control" required>
                                        <small class="text-muted">Must be at least 8 characters long</small>
                                    </div>
                                    <div class="mb-4">
                                        <label for="confirm_new_password" class="custom-form-label">Confirm New Password</label>
                                        <input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control custom-form-control" required>
                                    </div>
                                    <button type="submit" name="change_password" class="custom-btn">
                                        <i class="bi bi-key me-2"></i>Update Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="v-pills-orders" role="tabpanel" aria-labelledby="v-pills-orders-tab">
                        <div class="custom-card">
                            <div class="custom-card-header">
                                <h5><i class="bi bi-clock-history me-2"></i>Order History</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($orders)): ?>
                                    <div class="empty-state">
                                        <i class="bi bi-bag-x"></i>
                                        <h6>No Orders Yet</h6>
                                        <p class="mb-0">You haven't placed any orders yet. Start shopping to see your order history here!</p>
                                        <a href="all-products" class="custom-btn mt-3">
                                            <i class="bi bi-shop me-2"></i>Start Shopping
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table custom-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Date</th>
                                                    <th>Total</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orders as $order): ?>
                                                    <tr>
                                                        <td>
                                                            <strong>#<?= esc_html($order['id']) ?></strong>
                                                        </td>
                                                        <td>
                                                            <div class="text-muted small">
                                                                <?= format_date($order['created_at']) ?>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <strong><?= formatPrice($order['total_amount']) ?></strong>
                                                        </td>
                                                        <td>
                                                            <span class="status-badge <?= $order['status'] === 'Completed' ? 'status-completed' : 'status-pending' ?>">
                                                                <?= esc_html($order['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <a href="order-details?id=<?= $order['id'] ?>" class="custom-btn-outline">
                                                                <i class="bi bi-eye me-1"></i>View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Add smooth transitions when switching tabs
    document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            e.target.closest('.nav-link').scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        });
    });

    // Add loading state to form submissions
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

                // Re-enable after a few seconds as a fallback in case of JS errors
                setTimeout(() => {
                    if (submitBtn.disabled) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                }, 4000);
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
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
$success_message = '';

// STEP 3: Handle all form submissions BEFORE any HTML output.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Handle Profile Details Update ---
    if (isset($_POST['update_profile'])) {
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

        // Check if email is being changed to one that already exists
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $errors[] = "This email address is already in use by another account.";
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            if ($stmt->execute([$username, $email, $phone, $address, $user_id])) {
                // Update session name for the header greeting
                $_SESSION['user_name'] = $username;
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Profile updated successfully!'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Failed to update profile.'];
            }
            redirect('profile');
        }
    }

    // --- Handle Password Change ---
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_new_password = $_POST['confirm_new_password'] ?? '';

        // Fetch current user's hashed password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($current_password, $user['password'])) {
            $errors[] = "Your current password is not correct.";
        }
        if (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters long.";
        }
        if ($new_password !== $confirm_new_password) {
            $errors[] = "New passwords do not match.";
        }

        if (empty($errors)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashed_password, $user_id])) {
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Password changed successfully!'];
            } else {
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Failed to change password.'];
            }
            redirect('profile');
        }
    }
}

// STEP 4: Fetch data for page display AFTER processing forms.
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch user's order history
$orders_stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$orders_stmt->execute([$user_id]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// STEP 5: Now, include the header.
include 'includes/header.php';
?>

<style>
    :root {
        --primary-color: #6366f1;
        --primary-hover: #5b5dd8;
        --secondary-color: #f8fafc;
        --dark-color: #0f172a;
        --text-color: #334155;
        --light-text: #64748b;
        --border-color: #e2e8f0;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 16px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .profile-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .profile-header {
        background: white;
        border-radius: var(--radius-lg);
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-lg);
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .profile-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), #8b5cf6, #ec4899);
    }

    .profile-avatar {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, var(--primary-color), #8b5cf6);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: white;
        margin: 0 auto 1rem;
        box-shadow: var(--shadow-md);
        border: 4px solid white;
    }

    .custom-sidebar {
        background: white;
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        box-shadow: var(--shadow-md);
        height: fit-content;
        position: sticky;
        top: 2rem;
    }

    .custom-nav-pills .nav-link {
        background: none;
        border: none;
        color: var(--text-color);
        font-weight: 500;
        padding: 1rem 1.5rem;
        margin-bottom: 0.5rem;
        border-radius: var(--radius-md);
        transition: var(--transition);
        text-align: left;
        width: 100%;
        display: flex;
        align-items: center;
        position: relative;
    }

    .custom-nav-pills .nav-link:hover {
        background: var(--secondary-color);
        color: var(--primary-color);
        transform: translateX(4px);
    }

    .custom-nav-pills .nav-link.active {
        background: linear-gradient(135deg, var(--primary-color), #8b5cf6);
        color: white;
        box-shadow: var(--shadow-md);
    }

    .custom-nav-pills .nav-link i {
        width: 20px;
        margin-right: 0.75rem;
    }

    .custom-card {
        background: white;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-md);
        border: none;
        overflow: hidden;
        transition: var(--transition);
    }

    .custom-card:hover {
        box-shadow: var(--shadow-lg);
        transform: translateY(-2px);
    }

    .custom-card-header {
        background: linear-gradient(135deg, var(--secondary-color), #f1f5f9);
        border-bottom: 1px solid var(--border-color);
        padding: 1.5rem;
        position: relative;
    }

    .custom-card-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, var(--primary-color), #8b5cf6);
    }

    .custom-card-header h5 {
        margin: 0;
        font-weight: 600;
        color: var(--dark-color);
    }

    .custom-form-control {
        border: 2px solid var(--border-color);
        border-radius: var(--radius-md);
        padding: 0.875rem 1rem;
        background: var(--secondary-color);
        transition: var(--transition);
        font-size: 0.875rem;
    }

    .custom-form-control:focus {
        border-color: var(--primary-color);
        background: white;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .custom-form-label {
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }

    .custom-btn {
        background: linear-gradient(135deg, var(--primary-color), #8b5cf6);
        border: none;
        color: white;
        padding: 0.875rem 2rem;
        border-radius: var(--radius-md);
        font-weight: 500;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .custom-btn:hover {
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
        color: white;
    }

    .custom-btn:active {
        transform: translateY(0);
    }

    .custom-alert {
        border: none;
        border-radius: var(--radius-md);
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        border-left: 4px solid;
    }

    .custom-alert.alert-danger {
        background: #fef2f2;
        border-left-color: var(--danger-color);
        color: #dc2626;
    }

    .custom-alert.alert-success {
        background: #f0fdf4;
        border-left-color: var(--success-color);
        color: #16a34a;
    }

    .custom-table {
        border-radius: var(--radius-md);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .custom-table thead th {
        background: var(--secondary-color);
        border: none;
        font-weight: 600;
        color: var(--text-color);
        padding: 1rem;
        font-size: 0.875rem;
    }

    .custom-table tbody td {
        border: none;
        padding: 1rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .custom-table tbody tr:hover {
        background: var(--secondary-color);
    }

    .status-badge {
        padding: 0.375rem 0.875rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-completed {
        background: #dcfce7;
        color: #16a34a;
    }

    .status-pending {
        background: #fef3c7;
        color: #d97706;
    }

    .custom-btn-outline {
        background: none;
        border: 2px solid var(--primary-color);
        color: var(--primary-color);
        padding: 0.5rem 1rem;
        border-radius: var(--radius-sm);
        font-weight: 500;
        transition: var(--transition);
        font-size: 0.875rem;
    }

    .custom-btn-outline:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-1px);
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--light-text);
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }

    @media (max-width: 768px) {
        .profile-container {
            padding: 1rem 0;
        }

        .profile-header {
            margin-bottom: 1rem;
            padding: 1.5rem;
        }

        .custom-sidebar {
            margin-bottom: 1rem;
            position: static;
        }

        .custom-card {
            margin-bottom: 1rem;
        }
    }

    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<div class="profile-container">
    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header fade-in">
            <div class="profile-avatar">
                <i class="bi bi-person-fill"></i>
            </div>
            <h2 class="mb-1"><?= esc_html($user['username']) ?></h2>
            <p class="text-muted mb-0"><?= esc_html($user['email']) ?></p>
        </div>

        <div class="row">
            <!-- Sidebar Navigation -->
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

            <!-- Main Content -->
            <div class="col-lg-9 col-md-8">
                <div class="tab-content fade-in" id="v-pills-tabContent">

                    <!-- Display any validation errors here -->
                    <?php if (!empty($errors)): ?>
                        <div class="custom-alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= esc_html($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Profile Details Tab -->
                    <div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel" aria-labelledby="v-pills-profile-tab">
                        <div class="custom-card">
                            <div class="custom-card-header">
                                <h5><i class="bi bi-person-badge me-2"></i>My Profile Details</h5>
                            </div>
                            <div class="card-body p-4">
                                <form action="profile" method="post">
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

                    <!-- Change Password Tab -->
                    <div class="tab-pane fade" id="v-pills-password" role="tabpanel" aria-labelledby="v-pills-password-tab">
                        <div class="custom-card">
                            <div class="custom-card-header">
                                <h5><i class="bi bi-shield-lock me-2"></i>Change Password</h5>
                            </div>
                            <div class="card-body p-4">
                                <form action="profile" method="post">
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

                    <!-- Order History Tab -->
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
                submitBtn.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Processing...';

                // Re-enable after 3 seconds as fallback
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 3000);
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
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

<div class="page-header" style="background-color: #f8f9fa; padding: 2rem 0; margin-bottom: 3rem;">
    <div class="container">
        <h1 class="display-5 fw-bold">My Account</h1>
        <p class="text-muted">Manage your profile, address, and view your order history.</p>
    </div>
</div>

<main class="container my-5">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3">
            <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                <button class="nav-link active text-start" id="v-pills-profile-tab" data-bs-toggle="pill" data-bs-target="#v-pills-profile" type="button" role="tab" aria-controls="v-pills-profile" aria-selected="true">
                    <i class="bi bi-person-fill me-2"></i>Profile Details
                </button>
                <button class="nav-link text-start" id="v-pills-password-tab" data-bs-toggle="pill" data-bs-target="#v-pills-password" type="button" role="tab" aria-controls="v-pills-password" aria-selected="false">
                    <i class="bi bi-key-fill me-2"></i>Change Password
                </button>
                <button class="nav-link text-start" id="v-pills-orders-tab" data-bs-toggle="pill" data-bs-target="#v-pills-orders" type="button" role="tab" aria-controls="v-pills-orders" aria-selected="false">
                    <i class="bi bi-box-seam-fill me-2"></i>Order History
                </button>
                <a class="nav-link text-start" href="logout">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div class="tab-content" id="v-pills-tabContent">

                <!-- Display any validation errors here -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= esc_html($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Profile Details Tab -->
                <div class="tab-pane fade show active" id="v-pills-profile" role="tabpanel" aria-labelledby="v-pills-profile-tab">
                    <div class="card">
                        <div class="card-header">
                            <h5>My Details</h5>
                        </div>
                        <div class="card-body">
                            <form action="profile" method="post">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Full Name</label>
                                    <input type="text" name="username" id="username" class="form-control" value="<?= esc_html($user['username']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" name="email" id="email" class="form-control" value="<?= esc_html($user['email']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" id="phone" class="form-control" value="<?= esc_html($user['phone']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Full Address</label>
                                    <textarea name="address" id="address" class="form-control" rows="3" required><?= esc_html($user['address']) ?></textarea>
                                </div>
                                <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Change Password Tab -->
                <div class="tab-pane fade" id="v-pills-password" role="tabpanel" aria-labelledby="v-pills-password-tab">
                    <div class="card">
                        <div class="card-header">
                            <h5>Change My Password</h5>
                        </div>
                        <div class="card-body">
                            <form action="profile" method="post">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" name="new_password" id="new_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control" required>
                                </div>
                                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Order History Tab -->
                <div class="tab-pane fade" id="v-pills-orders" role="tabpanel" aria-labelledby="v-pills-orders-tab">
                    <div class="card">
                        <div class="card-header">
                            <h5>My Order History</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($orders)): ?>
                                <p class="text-muted">You have not placed any orders yet.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
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
                                                <td>#<?= esc_html($order['id']) ?></td>
                                                <td><?= format_date($order['created_at']) ?></td>
                                                <td><?= formatPrice($order['total_amount']) ?></td>
                                                <td>
                                                        <span class="badge <?= $order['status'] === 'Completed' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                            <?= esc_html($order['status']) ?>
                                                        </span>
                                                </td>
                                                <td>
                                                    <a href="order-details?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
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
</main>

<?php include 'includes/footer.php'; ?>

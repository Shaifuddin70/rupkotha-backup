<?php
require_once 'includes/header.php';
require_once 'includes/functions.php'; // Contains session_start(), db connection, and functions
require_once 'includes/settings-helper.php';

// --- Authentication Check ---
if (!isAdmin()) {
    redirect('login.php');
}

$admin_id = $_SESSION['admin_id'];

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $pdo->beginTransaction();
    try {
        // --- Part 1: Update Website Settings ---
        $settings_data = [
            'company_name' => trim($_POST['company_name']),
            'email' => filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL),
            'phone' => trim($_POST['phone']),
            'address' => trim($_POST['address']),
            'facebook' => filter_input(INPUT_POST, 'facebook', FILTER_VALIDATE_URL),
            'instagram' => filter_input(INPUT_POST, 'instagram', FILTER_VALIDATE_URL),
            'twitter' => filter_input(INPUT_POST, 'twitter', FILTER_VALIDATE_URL),
            'shipping_fee_dhaka' => filter_input(INPUT_POST, 'shipping_fee_dhaka', FILTER_VALIDATE_FLOAT),
            'shipping_fee_outside' => filter_input(INPUT_POST, 'shipping_fee_outside', FILTER_VALIDATE_FLOAT),
            'bkash_number' => trim($_POST['bkash_number']),
            'nagad_number' => trim($_POST['nagad_number']),
            'rocket_number' => trim($_POST['rocket_number'])
        ];

        // Use the get_setting helper here to get the current logo for the handleImageUpload function
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $settings_data['logo'] = handleImageUpload($_FILES['logo'], get_setting('logo'));
        }

        foreach ($settings_data as $key => $value) {
            if ($value !== false && $value !== null) {
                $stmt = $pdo->prepare("UPDATE settings SET `$key` = ? WHERE id = 1");
                $stmt->execute([$value]);
            }
        }

        // --- Part 2: Update Admin Details ---
        $admin_username = trim($_POST['admin_username']);
        $admin_email = filter_input(INPUT_POST, 'admin_email', FILTER_VALIDATE_EMAIL);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (!empty($admin_username) && $admin_email) {
            $admin_update_sql = "UPDATE admins SET username = ?, email = ? WHERE id = ?";
            $admin_params = [$admin_username, $admin_email, $admin_id];
            $pdo->prepare($admin_update_sql)->execute($admin_params);
        }

        if (!empty($new_password)) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?")->execute([$hashed_password, $admin_id]);
            } else {
                throw new Exception("New passwords do not match.");
            }
        }

        $pdo->commit();
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Settings updated successfully!'];

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'An error occurred: ' . $e->getMessage()];
    }

    redirect('settings.php');
}

// Fetch current settings for display
$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC) ?: [];
$admin_user = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$admin_user->execute([$admin_id]);
$admin = $admin_user->fetch(PDO::FETCH_ASSOC) ?: [];

?>

<h2 class="page-title mb-4">Website Settings</h2>

<div class="card shadow-sm">
    <div class="card-header">
        <!-- Nav Tabs -->
        <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general"
                        type="button" role="tab">General
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button"
                        role="tab">Contact & Social
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button"
                        role="tab">Payment & Shipping
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="admin-tab" data-bs-toggle="tab" data-bs-target="#admin" type="button"
                        role="tab">Admin Account
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <!-- Tab Content -->
            <div class="tab-content p-2" id="settingsTabsContent">
                <!-- General Settings Tab -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <h5 class="mb-3">General Information</h5>
                    <div class="mb-3"><label for="company_name" class="form-label">Company Name</label><input
                                type="text" name="company_name" id="company_name" class="form-control"
                                value="<?= esc_html($settings['company_name'] ?? '') ?>" required></div>
                    <div class="mb-3"><label for="logo" class="form-label">Company Logo</label><input type="file"
                                                                                                      name="logo"
                                                                                                      id="logo"
                                                                                                      class="form-control"><small
                                class="form-text text-muted">Upload a new logo to replace the current one.</small></div>
                    <?php if (!empty($settings['logo'])): ?>
                        <div class="mb-3"><p><strong>Current Logo:</strong></p><img
                                src="assets/uploads/<?= esc_html($settings['logo']) ?>" alt="Current Logo"
                                style="max-height: 80px; background-color: #f8f9fa; padding: 5px; border-radius: 5px;">
                        </div><?php endif; ?>
                </div>

                <!-- Social & Contact Tab -->
                <div class="tab-pane fade" id="social" role="tabpanel">
                    <h5 class="mb-3">Contact & Social Media</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="email" class="form-label">Public Email</label><input
                                    type="email" name="email" id="email" class="form-control"
                                    value="<?= esc_html($settings['email'] ?? '') ?>"></div>
                        <div class="col-md-6 mb-3"><label for="phone" class="form-label">Public Phone</label><input
                                    type="text" name="phone" id="phone" class="form-control"
                                    value="<?= esc_html($settings['phone'] ?? '') ?>"></div>
                    </div>
                    <div class="mb-3"><label for="address" class="form-label">Company Address</label><textarea
                                name="address" id="address" class="form-control"
                                rows="3"><?= esc_html($settings['address'] ?? '') ?></textarea></div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label for="facebook" class="form-label">Facebook URL</label><input
                                    type="url" name="facebook" id="facebook" class="form-control"
                                    value="<?= esc_html($settings['facebook'] ?? '') ?>"></div>
                        <div class="col-md-4 mb-3"><label for="instagram" class="form-label">Instagram URL</label><input
                                    type="url" name="instagram" id="instagram" class="form-control"
                                    value="<?= esc_html($settings['instagram'] ?? '') ?>"></div>
                        <div class="col-md-4 mb-3"><label for="twitter" class="form-label">Twitter URL</label><input
                                    type="url" name="twitter" id="twitter" class="form-control"
                                    value="<?= esc_html($settings['twitter'] ?? '') ?>"></div>
                    </div>
                </div>

                <!-- Payment & Shipping Tab -->
                <div class="tab-pane fade" id="payment" role="tabpanel">
                    <h5 class="mb-3">Shipping Fees</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="shipping_fee_dhaka" class="form-label">Shipping Fee
                                (Inside Dhaka)</label>
                            <div class="input-group"><span class="input-group-text">৳</span><input type="number"
                                                                                                   name="shipping_fee_dhaka"
                                                                                                   id="shipping_fee_dhaka"
                                                                                                   class="form-control"
                                                                                                   step="0.01"
                                                                                                   value="<?= esc_html($settings['shipping_fee_dhaka'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3"><label for="shipping_fee_outside" class="form-label">Shipping Fee
                                (Outside Dhaka)</label>
                            <div class="input-group"><span class="input-group-text">৳</span><input type="number"
                                                                                                   name="shipping_fee_outside"
                                                                                                   id="shipping_fee_outside"
                                                                                                   class="form-control"
                                                                                                   step="0.01"
                                                                                                   value="<?= esc_html($settings['shipping_fee_outside'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h5 class="mb-3">Mobile Payment Numbers</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label for="bkash_number" class="form-label">bKash
                                Number</label><input type="text" name="bkash_number" id="bkash_number"
                                                     class="form-control"
                                                     value="<?= esc_html($settings['bkash_number'] ?? '') ?>"></div>
                        <div class="col-md-4 mb-3"><label for="nagad_number" class="form-label">Nagad
                                Number</label><input type="text" name="nagad_number" id="nagad_number"
                                                     class="form-control"
                                                     value="<?= esc_html($settings['nagad_number'] ?? '') ?>"></div>
                        <div class="col-md-4 mb-3"><label for="rocket_number" class="form-label">Rocket
                                Number</label><input type="text" name="rocket_number" id="rocket_number"
                                                     class="form-control"
                                                     value="<?= esc_html($settings['rocket_number'] ?? '') ?>"></div>
                    </div>
                </div>

                <!-- Admin Account Tab -->
                <div class="tab-pane fade" id="admin" role="tabpanel">
                    <h5 class="mb-3">Admin Account Details</h5>
                    <p class="text-muted">Update your login credentials here. Only fill in the password fields if you
                        want to change your password.</p>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="admin_username" class="form-label">Admin Username</label><input
                                    type="text" name="admin_username" id="admin_username" class="form-control"
                                    value="<?= esc_html($admin['username'] ?? '') ?>" required></div>
                        <div class="col-md-6 mb-3"><label for="admin_email" class="form-label">Admin Email</label><input
                                    type="email" name="admin_email" id="admin_email" class="form-control"
                                    value="<?= esc_html($admin['email'] ?? '') ?>" required></div>
                    </div>
                    <hr>
                    <h5 class="mb-3">Change Password</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label for="new_password" class="form-label">New
                                Password</label><input type="password" name="new_password" id="new_password"
                                                       class="form-control"></div>
                        <div class="col-md-6 mb-3"><label for="confirm_password" class="form-label">Confirm New
                                Password</label><input type="password" name="confirm_password" id="confirm_password"
                                                       class="form-control"></div>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-end border-top pt-3">
                <button type="submit" name="update_settings" class="btn btn-primary btn-lg">Save All Settings</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';
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

        // Handle logo upload
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



<!-- Flash Messages -->
<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="alert-modern alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?= $_SESSION['flash_message']['type'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
        <strong><?= $_SESSION['flash_message']['type'] === 'success' ? 'Success!' : 'Error!' ?></strong>
        <?= $_SESSION['flash_message']['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<!-- Settings Card -->
<div class="settings-card">
    <!-- Navigation Tabs -->
    <div class="settings-nav">
        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general"
                    type="button" role="tab">
                    <i class="bi bi-building me-2"></i>General
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="social-tab" data-bs-toggle="tab" data-bs-target="#social" type="button"
                    role="tab">
                    <i class="bi bi-telephone me-2"></i>Contact & Social
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button"
                    role="tab">
                    <i class="bi bi-credit-card me-2"></i>Payment & Shipping
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="admin-tab" data-bs-toggle="tab" data-bs-target="#admin" type="button"
                    role="tab">
                    <i class="bi bi-person-gear me-2"></i>Admin Account
                </button>
            </li>
        </ul>
    </div>

    <!-- Form Content -->
    <form method="post" enctype="multipart/form-data" id="settingsForm">
        <div class="settings-tab-content">
            <div class="tab-content" id="settingsTabsContent">
                
                <!-- General Settings Tab -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="bi bi-building"></i>
                            </div>
                            <h3 class="section-title">Company Information</h3>
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="modern-form-group">
                                    <label for="company_name" class="modern-form-label">
                                        <i class="bi bi-buildings"></i>Company Name
                                    </label>
                                    <input type="text" name="company_name" id="company_name" 
                                           class="modern-form-control" 
                                           value="<?= esc_html($settings['company_name'] ?? '') ?>" 
                                           required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="bi bi-image"></i>
                            </div>
                            <h3 class="section-title">Company Logo</h3>
                        </div>
                        
                        <div class="modern-form-group">
                            <label for="logo" class="modern-form-label">
                                <i class="bi bi-upload"></i>Upload New Logo
                            </label>
                            <div class="file-upload-area" onclick="document.getElementById('logo').click()">
                                <i class="bi bi-cloud-upload" style="font-size: 2rem; color: var(--settings-primary); margin-bottom: 1rem;"></i>
                                <p class="mb-2"><strong>Click to upload</strong> or drag and drop</p>
                                <small class="text-muted">PNG, JPG, GIF up to 10MB</small>
                            </div>
                            <input type="file" name="logo" id="logo" class="d-none" accept="image/*">
                            <div class="form-text-modern">
                                <i class="bi bi-info-circle"></i>
                                Recommended size: 200x80 pixels for best results
                            </div>
                        </div>

                        <?php if (!empty($settings['logo'])): ?>
                            <div class="current-logo-display">
                                <p class="mb-3"><strong>Current Logo:</strong></p>
                                <img src="assets/uploads/<?= esc_html($settings['logo']) ?>" alt="Current Logo">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Contact & Social Tab -->
                <div class="tab-pane fade" id="social" role="tabpanel">
                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="bi bi-telephone"></i>
                            </div>
                            <h3 class="section-title">Contact Information</h3>
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="modern-form-group">
                                    <label for="email" class="modern-form-label">
                                        <i class="bi bi-envelope"></i>Public Email
                                    </label>
                                    <input type="email" name="email" id="email" class="modern-form-control"
                                           value="<?= esc_html($settings['email'] ?? '') ?>"
                                           placeholder="contact@yourcompany.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="modern-form-group">
                                    <label for="phone" class="modern-form-label">
                                        <i class="bi bi-phone"></i>Public Phone
                                    </label>
                                    <input type="text" name="phone" id="phone" class="modern-form-control"
                                           value="<?= esc_html($settings['phone'] ?? '') ?>"
                                           placeholder="+880 123 456 789">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="modern-form-group">
                                    <label for="address" class="modern-form-label">
                                        <i class="bi bi-geo-alt"></i>Company Address
                                    </label>
                                    <textarea name="address" id="address" class="modern-form-control" rows="4"
                                              placeholder="Enter your complete business address"><?= esc_html($settings['address'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="bi bi-share"></i>
                            </div>
                            <h3 class="section-title">Social Media Links</h3>
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="modern-form-group">
                                    <label for="facebook" class="modern-form-label">
                                        <i class="bi bi-facebook" style="color: #1877f2;"></i>Facebook URL
                                    </label>
                                    <input type="url" name="facebook" id="facebook" class="modern-form-control"
                                           value="<?= esc_html($settings['facebook'] ?? '') ?>"
                                           placeholder="https://facebook.com/yourpage">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="modern-form-group">
                                    <label for="instagram" class="modern-form-label">
                                        <i class="bi bi-instagram" style="color: #E4405F;"></i>Instagram URL
                                    </label>
                                    <input type="url" name="instagram" id="instagram" class="modern-form-control"
                                           value="<?= esc_html($settings['instagram'] ?? '') ?>"
                                           placeholder="https://instagram.com/yourprofile">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="modern-form-group">
                                    <label for="twitter" class="modern-form-label">
                                        <i class="bi bi-twitter" style="color: #1DA1F2;"></i>Twitter URL
                                    </label>
                                    <input type="url" name="twitter" id="twitter" class="modern-form-control"
                                           value="<?= esc_html($settings['twitter'] ?? '') ?>"
                                           placeholder="https://twitter.com/yourhandle">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment & Shipping Tab -->
                <div class="tab-pane fade" id="payment" role="tabpanel">
                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="bi bi-truck"></i>
                            </div>
                            <h3 class="section-title">Shipping Configuration</h3>
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="modern-form-group">
                                    <label for="shipping_fee_dhaka" class="modern-form-label">
                                        <i class="bi bi-building"></i>Shipping Fee (Inside Dhaka)
                                    </label>
                                    <div class="modern-input-group">
                                        <span class="input-group-text">৳</span>
                                        <input type="number" name="shipping_fee_dhaka" id="shipping_fee_dhaka"
                                               class="modern-form-control" step="0.01" min="0"
                                               value="<?= esc_html($settings['shipping_fee_dhaka'] ?? '') ?>"
                                               placeholder="60.00">
                                    </div>
                                    <div class="form-text-modern">
                                        <i class="bi bi-info-circle"></i>
                                        Standard delivery within Dhaka city
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="modern-form-group">
                                    <label for="shipping_fee_outside" class="modern-form-label">
                                        <i class="bi bi-globe"></i>Shipping Fee (Outside Dhaka)
                                    </label>
                                    <div class="modern-input-group">
                                        <span class="input-group-text">৳</span>
                                        <input type="number" name="shipping_fee_outside" id="shipping_fee_outside"
                                               class="modern-form-control" step="0.01" min="0"
                                               value="<?= esc_html($settings['shipping_fee_outside'] ?? '') ?>"
                                               placeholder="120.00">
                                    </div>
                                    <div class="form-text-modern">
                                        <i class="bi bi-info-circle"></i>
                                        Delivery to other cities in Bangladesh
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="bi bi-credit-card"></i>
                            </div>
                            <h3 class="section-title">Mobile Payment Methods</h3>
                        </div>
                        
                        <div class="payment-method-grid">
                            <div class="payment-method-card">
                                <img src="../assets/images/bkash.svg" alt="bKash" class="payment-logo">
                                <div class="modern-form-group">
                                    <label for="bkash_number" class="modern-form-label">
                                        <i class="bi bi-phone"></i>bKash Number
                                    </label>
                                    <input type="text" name="bkash_number" id="bkash_number" class="modern-form-control"
                                           value="<?= esc_html($settings['bkash_number'] ?? '') ?>"
                                           placeholder="01XXXXXXXXX">
                                    <div class="form-text-modern">
                                        <i class="bi bi-info-circle"></i>
                                        Personal or merchant number
                                    </div>
                                </div>
                            </div>
                            
                            <div class="payment-method-card">
                                <img src="../assets/images/nagad.svg" alt="Nagad" class="payment-logo">
                                <div class="modern-form-group">
                                    <label for="nagad_number" class="modern-form-label">
                                        <i class="bi bi-phone"></i>Nagad Number
                                    </label>
                                    <input type="text" name="nagad_number" id="nagad_number" class="modern-form-control"
                                           value="<?= esc_html($settings['nagad_number'] ?? '') ?>"
                                           placeholder="01XXXXXXXXX">
                                    <div class="form-text-modern">
                                        <i class="bi bi-info-circle"></i>
                                        Personal or merchant number
                                    </div>
                                </div>
                            </div>
                            
                            <div class="payment-method-card">
                                <img src="../assets/images/rocket.png" alt="Rocket" class="payment-logo">
                                <div class="modern-form-group">
                                    <label for="rocket_number" class="modern-form-label">
                                        <i class="bi bi-phone"></i>Rocket Number
                                    </label>
                                    <input type="text" name="rocket_number" id="rocket_number" class="modern-form-control"
                                           value="<?= esc_html($settings['rocket_number'] ?? '') ?>"
                                           placeholder="01XXXXXXXXX">
                                    <div class="form-text-modern">
                                        <i class="bi bi-info-circle"></i>
                                        Personal or merchant number
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Account Tab -->
                <div class="tab-pane fade" id="admin" role="tabpanel">
                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="bi bi-person-gear"></i>
                            </div>
                            <h3 class="section-title">Admin Account Details</h3>
                        </div>
                        
                        <div class="alert-modern alert-info">
                            <i class="bi bi-info-circle"></i>
                            <div>
                                <strong>Security Notice:</strong>
                                <p class="mb-0 mt-1">Update your login credentials here. Only fill in the password fields if you want to change your password.</p>
                            </div>
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="modern-form-group">
                                    <label for="admin_username" class="modern-form-label">
                                        <i class="bi bi-person"></i>Admin Username
                                    </label>
                                    <input type="text" name="admin_username" id="admin_username" 
                                           class="modern-form-control" 
                                           value="<?= esc_html($admin['username'] ?? '') ?>" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="modern-form-group">
                                    <label for="admin_email" class="modern-form-label">
                                        <i class="bi bi-envelope"></i>Admin Email
                                    </label>
                                    <input type="email" name="admin_email" id="admin_email" 
                                           class="modern-form-control" 
                                           value="<?= esc_html($admin['email'] ?? '') ?>" 
                                           required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <div class="section-header">
                            <div class="section-icon">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                            <h3 class="section-title">Change Password</h3>
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="modern-form-group">
                                    <label for="new_password" class="modern-form-label">
                                        <i class="bi bi-key"></i>New Password
                                    </label>
                                    <input type="password" name="new_password" id="new_password" 
                                           class="modern-form-control" 
                                           placeholder="Leave blank to keep current password">
                                    <div class="form-text-modern">
                                        <i class="bi bi-info-circle"></i>
                                        Minimum 6 characters recommended
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="modern-form-group">
                                    <label for="confirm_password" class="modern-form-label">
                                        <i class="bi bi-key-fill"></i>Confirm New Password
                                    </label>
                                    <input type="password" name="confirm_password" id="confirm_password" 
                                           class="modern-form-control" 
                                           placeholder="Confirm your new password">
                                    <div class="form-text-modern">
                                        <i class="bi bi-info-circle"></i>
                                        Must match the new password
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button Section -->
            <div class="d-flex justify-content-between align-items-center mt-4 pt-4" style="border-top: 2px solid var(--settings-border);">
                <div>
                    <small class="text-muted">
                        <i class="bi bi-clock me-1"></i>
                        Last updated: <?= date('M d, Y \a\t g:i A') ?>
                    </small>
                </div>
                <button type="submit" name="update_settings" class="settings-save-btn">
                    <i class="bi bi-check-lg me-2"></i>Save All Settings
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Enhanced JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // File upload enhancement
    const fileInput = document.getElementById('logo');
    const uploadArea = document.querySelector('.file-upload-area');
    
    if (fileInput && uploadArea) {
        // Handle file selection
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                uploadArea.innerHTML = `
                    <i class="bi bi-check-circle" style="font-size: 2rem; color: var(--settings-success); margin-bottom: 1rem;"></i>
                    <p class="mb-2"><strong>File selected:</strong> ${file.name}</p>
                    <small class="text-muted">Click to choose a different file</small>
                `;
            }
        });
        
        // Drag and drop functionality
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                const event = new Event('change', { bubbles: true });
                fileInput.dispatchEvent(event);
            }
        });
    }
    
    // Form validation
    const form = document.getElementById('settingsForm');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (form && newPassword && confirmPassword) {
        function validatePasswords() {
            if (newPassword.value && confirmPassword.value) {
                if (newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                    confirmPassword.classList.add('is-invalid');
                } else {
                    confirmPassword.setCustomValidity('');
                    confirmPassword.classList.remove('is-invalid');
                }
            }
        }
        
        newPassword.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
        
        form.addEventListener('submit', function(e) {
            validatePasswords();
            if (!confirmPassword.checkValidity() && newPassword.value) {
                e.preventDefault();
                confirmPassword.focus();
            }
        });
    }
    
    // Tab switching animation
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function() {
            const target = button.getAttribute('data-bs-target');
            const pane = document.querySelector(target);
            if (pane) {
                pane.style.opacity = '0';
                pane.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    pane.style.transition = 'all 0.3s ease';
                    pane.style.opacity = '1';
                    pane.style.transform = 'translateY(0)';
                }, 50);
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
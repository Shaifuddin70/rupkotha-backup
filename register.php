<?php
// This is the customer registration page, e.g., register

// STEP 1: Start the session and include necessary files FIRST.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';

// If the user is already logged in, they don't need to register.
if (isLoggedIn()) {
    redirect('index');
}

$errors = []; // An array to hold validation errors.

// STEP 2: Handle all form processing and potential redirects BEFORE any HTML output.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve form data
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // --- Validation ---
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
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "An account with this email address already exists.";
        }
    }

    // --- Process Registration ---
    if (empty($errors)) {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, email, password, phone, address) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$username, $email, $hashed_password, $phone, $address]);

            // Get the new user's ID
            $new_user_id = $pdo->lastInsertId();

            // Automatically log the new user in
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['user_name'] = $username;

            // Redirect to the homepage after successful registration
            // This now works because no HTML has been sent yet.
            redirect('index');

        } catch (PDOException $e) {
            $errors[] = "A database error occurred. Please try again later.";
        }
    }
}

// STEP 3: Now that all PHP logic is complete, include the header to start rendering the page.
include 'includes/header.php';
?>




<div class="register-body">
    <main class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 ">
                <div class="card register-card">
                    <div class="card-body p-4 p-sm-5">
                        <div class="text-center mb-4">
                            <a href="index"><img src="<?= esc_html($companyLogo) ?>" alt="Rupkotha Logo" style="max-height: 60px;" class="mb-3"></a>
                            <h2 class="fw-bold">Create Your Account</h2>
                            <p class="text-muted">Join us to get started with your shopping.</p>
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger" role="alert">
                                <h6 class="alert-heading">Please fix the following errors:</h6>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= esc_html($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post" novalidate>
                            <div class="form-floating mb-3">
                                <input type="text" name="username" id="username" class="form-control" placeholder="Full Name" required value="<?= esc_html($_POST['username'] ?? '') ?>">
                                <label for="username">Full Name</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" required value="<?= esc_html($_POST['email'] ?? '') ?>">
                                <label for="email">Email address</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="tel" name="phone" id="phone" class="form-control" placeholder="Phone Number" required value="<?= esc_html($_POST['phone'] ?? '') ?>">
                                <label for="phone">Phone Number</label>
                            </div>
                            <div class="form-floating mb-3">
                                <textarea name="address" id="address" class="form-control" placeholder="Your Address" style="height: 100px" required><?= esc_html($_POST['address'] ?? '') ?></textarea>
                                <label for="address">Full Address</label>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                                        <label for="password">Password</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-4">
                                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm Password" required>
                                        <label for="confirm_password">Confirm Password</label>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold">Create Account</button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="text-muted">Already have an account? <a href="login">Log In</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include 'includes/footer.php'; ?>

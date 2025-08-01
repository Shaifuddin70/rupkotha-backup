<?php
// This is the customer login page, e.g., login

// STEP 1: Start the session and include necessary files FIRST.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';

// If the user is already logged in, redirect them away from the login page.
if (isLoggedIn()) {
    redirect('index');
}

$error = '';

// STEP 2: Handle all form processing and potential redirects BEFORE any HTML output.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);

            // Store user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['username'];

            // This redirect now works because no HTML has been sent yet.
            redirect('index');
        } else {
            $error = "Invalid email or password!";
        }
    }
}

// STEP 3: Now that all PHP logic is complete, include the header to start rendering the page.
include 'includes/header.php';
?>

<!-- Custom styles for the login page -->


<div class="login-body">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="card login-card">
                    <div class="card-body p-4 p-sm-5">
                        <div class="text-center mb-4">
                            <a href="index"><img src="<?= esc_html($companyLogo) ?>" alt="Rupkotha Logo" style="max-height: 60px;" class="mb-3"></a>
                            <h2 class="fw-bold">Welcome Back!</h2>
                            <p class="text-muted">Log in to access your account and orders.</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <div>
                                    <?= esc_html($error) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="post" novalidate>
                            <div class="form-floating mb-3">
                                <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" required value="<?= esc_html($_POST['email'] ?? '') ?>">
                                <label for="email">Email address</label>
                            </div>
                            <div class="form-floating mb-4">
                                <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                                <label for="password">Password</label>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg fw-bold">Log In</button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="text-muted">Don't have an account? <a href="register">Sign Up</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

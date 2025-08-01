<?php
// We don't need a global $pdo here. It should be included via db.php.
session_start();

// If the admin is already logged in, redirect them to the dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'includes/db.php';
require_once 'includes/functions.php'; // Using the new functions file

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // It's good practice to check if the inputs are set
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            // Let's use the 'username' from the DB for a personalized welcome
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid email or password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rupkotha - Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f8f9fa;
            background-image: linear-gradient(to right top, #f8f9fa, #f0f2f8, #e8ecf6, #dee6f4, #d4e0f2);
        }
        .login-card {
            max-width: 450px;
            width: 100%;
            border: none;
            border-radius: 1rem;
        }
        .form-control-lg {
            min-height: calc(1.5em + 1rem + 2px);
            padding: 0.75rem 1rem;
        }
    </style>
</head>
<body>
<main class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="card shadow-lg login-card">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold">Rupkotha Admin Panel</h2>
                        <p class="text-muted">Welcome back! Please log in to continue.</p>
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
                            <input type="email" name="email" id="email" class="form-control form-control-lg" placeholder="name@example.com" required value="<?= esc_html($_POST['email'] ?? '') ?>">
                            <label for="email">Email address</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" name="password" id="password" class="form-control form-control-lg" placeholder="Password" required>
                            <label for="password">Password</label>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold">Log In</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>

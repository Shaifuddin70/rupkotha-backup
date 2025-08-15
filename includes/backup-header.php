<?php
// It's best practice to have session_start() at the very top of your entry point file (e.g., index)
// before any output. But if it's in each header, this works too.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_file = basename($_SERVER['PHP_SELF'], '.php'); // Get current filename without extension

$page_name = ($page_file === 'index') ? 'Home' : ucfirst(str_replace(['-', '_'], ' ', $page_file));

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Use require_once to prevent multiple inclusions
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php'; // Contains esc_html(), formatPrice(), etc.

// --- Data Fetching ---

// Load company settings with defaults
$settings_stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $settings_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$companyName = $settings['company_name'] ?? 'Rupkotha';
$companyLogo = !empty($settings['logo']) ? 'admin/assets/uploads/' . esc_html($settings['logo']) : 'assets/images/default-logo.png';

// Load categories for navigation
$categories_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get cart details from session
$cart_items_count = count($_SESSION['cart'] ?? []);
$cart_total_amount = 0;
if (!empty($_SESSION['cart'])) {
    // To calculate total, we need product prices. This is a more robust way.
    $product_ids = array_keys($_SESSION['cart']);
    if (!empty($product_ids)) {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));

        $cart_products_stmt = $pdo->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
        $cart_products_stmt->execute($product_ids);
        $cart_products = $cart_products_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            if (isset($cart_products[$product_id])) {
                $cart_total_amount += $cart_products[$product_id] * $quantity;
            }
        }
    }
}

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc_html($page_name) ?> - <?= esc_html($companyName) ?> | Your Online Store</title>

    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="assets/css/updated-css.css">
</head>

<body>
    <header class="custom-header">
        <!-- Main Navigation Bar -->
        <nav class="navbar navbar-expand-lg">
            <div class="container py-2">
                <!-- Logo -->
                <a class="navbar-brand me-lg-4" href="index">
                    <img src="<?= esc_html($companyLogo) ?>" alt="<?= esc_html($companyName) ?> Logo" class="brand-logo">
                </a>

                <!-- Mobile: User & Cart Icons (shown only on mobile) -->
                <div class="d-flex align-items-center d-lg-none ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <a class="nav-icon-btn me-2" href="profile">
                            <i class="bi bi-person-circle fs-4"></i>
                        </a>
                    <?php else: ?>
                        <a class="nav-icon-btn me-2" href="login">
                            <i class="bi bi-person fs-4"></i>
                        </a>
                    <?php endif; ?>

                    <a class="nav-icon-btn me-3" href="cart">
                        <i class="bi bi-bag fs-4"></i>
                        <?php if ($cart_items_count > 0): ?>
                            <span class="cart-badge"><?= $cart_items_count ?></span>
                        <?php endif; ?>
                    </a>

                    <button class="mobile-menu-btn" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                        aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                        <i class="bi bi-list fs-4"></i>
                    </button>
                </div>

                <!-- Navbar Content -->
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <!-- Search Form (centered on desktop, first on mobile) -->
                    <div class="mx-auto my-3 my-lg-0">
                        <form class="custom-search" role="search" action="search" method="get">
                            <input class="form-control" type="search" name="q"
                                placeholder="Search for amazing products..." aria-label="Search">
                            <button class="search-btn" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Main Site Navigation (for mobile view) -->
                    <div class="mobile-menu d-lg-none">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="index">
                                    <i class="bi bi-house me-2"></i>Home
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="all-products">
                                    <i class="bi bi-grid me-2"></i>All Products
                                </a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-tags me-2"></i>Categories
                                </a>
                                <ul class="dropdown-menu custom-dropdown">
                                    <?php foreach ($categories as $cat): ?>
                                        <li><a class="dropdown-item" href="category?id=<?= $cat['id'] ?>">
                                                <?= esc_html($cat['name']) ?>
                                            </a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="about">
                                    <i class="bi bi-info-circle me-2"></i>About Us
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="contact">
                                    <i class="bi bi-envelope me-2"></i>Contact Us
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Desktop User Actions (right-aligned, hidden on mobile) -->
                    <div class="d-none d-lg-flex align-items-center gap-3">
                        <?php if (isLoggedIn()): ?>
                            <div class="dropdown user-dropdown">
                                <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-person-circle me-2"></i>
                                    <?= esc_html($_SESSION['user_name'] ?? 'User') ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end custom-dropdown">
                                    <li><a class="dropdown-item" href="profile">
                                            <i class="bi bi-person me-2"></i>My Profile
                                        </a></li>
                                    <li><a class="dropdown-item" href="orders">
                                            <i class="bi bi-box-seam me-2"></i>My Orders
                                        </a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="logout">
                                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                                        </a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a class="login-btn" href="login">
                                <i class="bi bi-person"></i>
                                Join Us
                            </a>
                        <?php endif; ?>

                        <a class="nav-icon-btn position-relative" href="cart">
                            <i class="bi bi-bag fs-4"></i>
                            <?php if ($cart_items_count > 0): ?>
                                <span class="cart-badge"><?= $cart_items_count ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Category Navigation Bar (Desktop Only) -->
        <nav class="category-nav d-none d-lg-block">
            <div class="container">
                <ul class="nav justify-content-center">
                    <li class="nav-item">
                        <a class="nav-link" href="index">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="all-products">All Products</a>
                    </li>
                    <!-- Categories Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Categories
                        </a>
                        <ul class="dropdown-menu custom-dropdown">
                            <?php foreach ($categories as $cat): ?>
                                <li><a class="dropdown-item" href="category?id=<?= $cat['id'] ?>">
                                        <?= esc_html($cat['name']) ?>
                                    </a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact">Contact Us</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    <!-- End of Header -->

    <script>
        // Add scroll effect to header
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.custom-header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    </script>

    <!-- The rest of your page content (e.g., from index) will go here -->
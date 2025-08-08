<?php
// It's best practice to have session_start() at the very top of your entry point file (e.g., index.php)
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

    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/custom.css">

    <!-- Custom Styles for custom Header -->

</head>

<body>

    <header class="header-sticky bg-light">
        <!-- Main Navigation Bar -->
        <nav class="navbar navbar-expand-lg bg-light">
            <div class="container">
                <!-- Logo -->
                <a class="navbar-brand me-lg-4" href="index">
                    <img src="<?= esc_html($companyLogo) ?>" alt="<?= esc_html($companyName) ?> Logo">
                </a>

                <!-- Mobile: User & Cart Icons (shown only on mobile) -->
                <div class="d-flex align-items-center d-lg-none ms-auto">
                    <?php if (isLoggedIn()): ?>
                        <a class="nav-link me-3" href="profile"><i class="bi bi-person-circle fs-4"></i></a>
                    <?php else: ?>
                        <a class="nav-link me-3" href="login"><i class="bi bi-person-circle fs-4"></i></a>
                    <?php endif; ?>
                    <a class="nav-link position-relative me-3" href="cart">
                        <i class="bi bi-cart3 fs-4"></i>
                        <?php if ($cart_items_count > 0): ?>
                            <span class="badge bg-primary rounded-pill"><?= $cart_items_count ?></span>
                        <?php endif; ?>
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                        aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>

                <!-- Navbar Content -->
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <!-- Search Form (centered on desktop, first on mobile) -->
                    <form class="d-flex mx-auto search-form my-3 my-lg-0" role="search" action="search" method="get">
                        <input class="form-control me-2" type="search" name="q" placeholder="Search for products..."
                            aria-label="Search">
                        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
                    </form>

                    <!-- Main Site Navigation (for mobile view) -->
                    <ul class="navbar-nav d-lg-none">
                        <li class="nav-item">
                            <a class="nav-link" href="index">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="all-products">All Products</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                Categories
                            </a>
                            <ul class="dropdown-menu">
                                <?php foreach ($categories as $cat): ?>
                                    <li><a class="dropdown-item"
                                            href="category?id=<?= $cat['id'] ?>"><?= esc_html($cat['name']) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="about">About Us</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="contact">Contact Us</a>
                        </li>
                        <hr>
                    </ul>

                    <!-- Desktop User Actions (right-aligned, hidden on mobile) -->
                    <ul class="navbar-nav  mb-2 mb-lg-0 align-items-center d-none d-lg-flex">
                        <?php if (isLoggedIn()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="bi bi-person-circle fs-5 me-1"></i>
                                    Hello, <?= esc_html($_SESSION['user_name'] ?? 'User') ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="profile">My Profile</a></li>
                                    <li><a class="dropdown-item" href="orders">My Orders</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="logout">Logout</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="login">
                                    <i class="bi bi-person-circle fs-5 me-1"></i> Login/Register
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item">
                            <a class="nav-link position-relative" href="cart">
                                <i class="bi bi-cart3 fs-4"></i>
                                <?php if ($cart_items_count > 0): ?>
                                    <span class="badge bg-primary rounded-pill"><?= $cart_items_count ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Category Navigation Bar (Desktop Only) -->
        <nav class="category-nav d-none d-lg-block">
            <div class="container">
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="all-products">All Products</a>
                    </li>
                    <!-- Categories Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownCategories" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            Categories
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdownCategories">
                            <?php foreach ($categories as $cat): ?>
                                <li><a class="dropdown-item"
                                        href="category?id=<?= $cat['id'] ?>"><?= esc_html($cat['name']) ?></a></li>
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

    <!-- The rest of your page content (e.g., from index.php) will go here -->
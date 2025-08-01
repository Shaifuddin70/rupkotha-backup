<?php
session_start();
ob_start();
require_once '../includes/db.php';
require_once 'includes/auth.php'; // checks if admin is logged in
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Rupkotha Properties Bangladesh</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css"/>
    <link rel="stylesheet" href="assets/css/custom.css"/>
    <!-- Bootstrap JS Bundle with Popper -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0"/>
</head>
<body>
<!-- Navbar -->
<nav class="site-nav">
    <button class="sidebar-toggle">
        <span class="material-symbols-rounded">menu</span>
    </button>
</nav>

<div class="admin-container">
    <!-- Sidebar -->
    <aside class="sidebar collapsed">
        <div class="sidebar-header">
            <?php
            $settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch();
            $logo = !empty($settings['logo']) ? '../admin/assets/uploads/' . $settings['logo'] : '../admin/assets/images/logo.jpg';
            ?>
            <img src="<?= $logo ?>" alt="Admin Panel" class="header-logo"/>
            <p class="user_name"><?= isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Admin'; ?></p>
            <button class="sidebar-toggle">
                <span class="material-symbols-rounded">chevron_left</span>
            </button>
        </div>

        <div class="sidebar-content">
            <!-- Optional Search -->
            <form action="#" class="search-form">
                <span class="material-symbols-rounded">search</span>
                <input type="search" placeholder="Search..."/>
            </form>

            <!-- Sidebar Menu -->
            <ul class="menu-list">
                <li class="menu-item"><a href="index" class="menu-link"><span
                                class="material-symbols-rounded">dashboard</span><span
                                class="menu-label">Dashboard</span></a></li>
                <li class="menu-item"><a href="categories" class="menu-link"><span class="material-symbols-rounded">category</span><span
                                class="menu-label">Categories</span></a></li>
                <li class="menu-item"><a href="products" class="menu-link"><span class="material-symbols-rounded">storefront</span><span
                                class="menu-label">Products</span></a></li>
                <li class="menu-item"><a href="hero-slider" class="menu-link"><span class="material-symbols-rounded">storefront</span><span
                                class="menu-label">Hero Slider</span></a></li>
                <li class="menu-item"><a href="orders" class="menu-link"><span
                                class="material-symbols-rounded">receipt</span><span
                                class="menu-label">Orders</span></a></li>
                <li class="menu-item"><a href="customers" class="menu-link"><span
                                class="material-symbols-rounded">group</span><span
                                class="menu-label">Customers</span></a></li>
                <li class="menu-item"><a href="settings" class="menu-link"><span class="material-symbols-rounded">settings</span><span
                                class="menu-label">Settings</span></a></li>
                <li class="menu-item"><a href="logout" class="menu-link text-danger"><span
                                class="material-symbols-rounded">logout</span><span class="menu-label">Logout</span></a>
                </li>
            </ul>
        </div>

        <div class="sidebar-footer">
            <button class="theme-toggle">
                <div class="theme-label">
                    <span class="theme-icon material-symbols-rounded">dark_mode</span>
                    <span class="theme-text">Dark Mode</span>
                </div>
                <div class="theme-toggle-track">
                    <div class="theme-toggle-indicator"></div>
                </div>
            </button>
        </div>
    </aside>

    <!-- Start main content -->
    <div class="main-content">

<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// --- VIEW STATE (Active vs. Deleted) ---
$view_deleted = isset($_GET['view_deleted']) && $_GET['view_deleted'] == 1;

// --- PAGINATION SETUP ---
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$per_page = 10;
$offset = ($page - 1) * $per_page;

$count_sql = "SELECT COUNT(id) FROM products WHERE " . ($view_deleted ? "deleted_at IS NOT NULL" : "deleted_at IS NULL");
$total_products = $pdo->query($count_sql)->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// --- FORM HANDLING for ADD Product ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $short_description = trim($_POST['short_description']);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $cost_price = filter_input(INPUT_POST, 'cost_price', FILTER_VALIDATE_FLOAT);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $description = trim($_POST['description']);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

    if (empty($name) || $price === false || $cost_price === false || $category_id === false || $stock === null) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid input for new product. Please check all fields.'];
    } else {
        $pdo->beginTransaction();
        try {
            $image_name = handleImageUpload($_FILES['image']);
            if ($image_name !== false) {
                $insert_stmt = $pdo->prepare("INSERT INTO products (name, short_description, price, cost_price, category_id, image, description, stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $insert_stmt->execute([$name, $short_description, $price, $cost_price, $category_id, $image_name, $description, $stock]);

                $product_id = $pdo->lastInsertId();
                handleAdditionalImages($_FILES['additional_images'], $product_id, $pdo);

                $pdo->commit();
                $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Product added successfully!'];
            } else {
                $pdo->rollBack();
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'A main image is required. Product not added.'];
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'An error occurred: ' . $e->getMessage()];
        }
    }
    redirect('products.php');
}

// --- FORM HANDLING for UPDATE Product ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $short_description = trim($_POST['short_description']);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $cost_price = filter_input(INPUT_POST, 'cost_price', FILTER_VALIDATE_FLOAT);
    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $description = trim($_POST['description']);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

    if (!$product_id || empty($name) || $price === false || $cost_price === false || $category_id === false || $stock === null) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid input for product update. Please check all fields.'];
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $current_image = $stmt->fetchColumn();
            $image_to_save = handleImageUpload($_FILES['image'], $current_image);

            $update_stmt = $pdo->prepare(
                "UPDATE products SET name = ?, short_description = ?, price = ?, cost_price = ?, category_id = ?, description = ?, stock = ?, image = ?, updated_at = NOW() WHERE id = ?"
            );
            $update_stmt->execute([$name, $short_description, $price, $cost_price, $category_id, $description, $stock, $image_to_save, $product_id]);

            handleAdditionalImages($_FILES['additional_images'], $product_id, $pdo);

            $pdo->commit();
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => "Product #" . $product_id . " was updated successfully."];
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'An error occurred during update: ' . $e->getMessage()];
        }
    }
    redirect('products.php');
}

// Fetch categories for dropdown
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch products based on the view
$fetch_sql = "SELECT p.*, c.name AS category_name
              FROM products p
              JOIN categories c ON p.category_id = c.id
              WHERE " . ($view_deleted ? "p.deleted_at IS NOT NULL" : "p.deleted_at IS NULL") . "
              ORDER BY p.id DESC
              LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($fetch_sql);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>



<head>
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-light: #818cf8;
            --primary-dark: #4f46e5;
            --secondary-color: #f1f5f9;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            min-height: 100vh;
            color: var(--dark-color);
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            padding: 2.5rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 2rem 2rem;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        }

        .page-header .container {
            position: relative;
            z-index: 1;
        }

        .page-title {
            font-weight: 700;
            font-size: 2.5rem;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .page-subtitle {
            opacity: 0.9;
            margin-top: 0.5rem;
            font-size: 1.1rem;
            font-weight: 400;
        }

        .stats-row {
            margin-top: 1.5rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            color: white;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.2);
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
        }

        .stat-label {
            font-size: 0.875rem;
            opacity: 0.9;
            margin: 0;
        }

        .main-card {
            background: white;
            border-radius: 1.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-header-modern {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .card-title-modern {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
            color: var(--dark-color);
        }

        .btn-modern {
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-modern:hover::before {
            left: 100%;
        }

        .btn-primary-modern {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
        }

        .btn-outline-modern {
            background: white;
            border: 2px solid var(--border-color);
            color: var(--dark-color);
        }

        .btn-outline-modern:hover {
            background: var(--secondary-color);
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-1px);
        }

        .table-modern {
            margin: 0;
        }

        .table-modern thead th {
            background: var(--secondary-color);
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .table-modern tbody td {
            padding: 1.25rem 1.5rem;
            border: none;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .table-modern tbody tr {
            transition: all 0.2s ease;
        }

        .table-modern tbody tr:hover {
            background: linear-gradient(135deg, #fafbfc 0%, #f8fafc 100%);
            transform: scale(1.005);
            box-shadow: var(--shadow-sm);
        }

        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 0.75rem;
            object-fit: cover;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
        }

        .product-image:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-lg);
        }

        .badge-modern {
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 500;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-category {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: var(--primary-color);
        }

        .badge-status {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: var(--success-color);
        }

        .badge-deleted {
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            color: var(--danger-color);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            text-decoration: none;
        }

        .btn-action:hover {
            transform: translateY(-1px);
        }

        .btn-edit {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1d4ed8;
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #bfdbfe 0%, #93c5fd 100%);
            color: #1e40af;
        }

        .btn-images {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
        }

        .btn-images:hover {
            background: linear-gradient(135deg, #a7f3d0 0%, #6ee7b7 100%);
            color: #047857;
        }

        .btn-delete {
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            color: #dc2626;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #fca5a5 0%, #f87171 100%);
            color: #b91c1c;
        }

        .btn-restore {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #d97706;
        }

        .btn-restore:hover {
            background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%);
            color: #b45309;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #ccc;
            transition: 0.4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: white;
            transition: 0.4s;
            border-radius: 50%;
            box-shadow: var(--shadow-sm);
        }

        input:checked+.slider {
            background: var(--success-color);
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        .pagination-modern {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        .page-link-modern {
            border: none;
            padding: 0.75rem 1rem;
            margin: 0 0.25rem;
            border-radius: 0.5rem;
            color: var(--dark-color);
            background: white;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }

        .page-link-modern:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .page-item.active .page-link-modern {
            background: var(--primary-color);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .modal-modern .modal-content {
            border-radius: 1rem;
            border: none;
            box-shadow: var(--shadow-xl);
        }

        .modal-modern .modal-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-bottom: 1px solid var(--border-color);
            border-radius: 1rem 1rem 0 0;
            padding: 1.5rem 2rem;
        }

        .modal-modern .modal-title {
            font-weight: 600;
            font-size: 1.25rem;
        }

        .modal-modern .modal-body {
            padding: 2rem;
        }

        .form-control-modern {
            border-radius: 0.75rem;
            border: 2px solid var(--border-color);
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
        }

        .form-control-modern:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .form-label-modern {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .filter-tabs {
            display: flex;
            background: white;
            border-radius: 1rem;
            padding: 0.25rem;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1rem;
        }

        .filter-tab {
            flex: 1;
            text-align: center;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            text-decoration: none;
            color: var(--dark-color);
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .filter-tab.active {
            background: var(--primary-color);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .price-display {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .cost-price {
            color: #64748b;
            font-size: 0.875rem;
        }

        .selling-price {
            color: var(--success-color);
        }

        .stock-indicator {
            font-weight: 500;
        }

        .stock-low {
            color: var(--danger-color);
        }

        .stock-medium {
            color: var(--warning-color);
        }

        .stock-good {
            color: var(--success-color);
        }

        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .card-header-modern {
                padding: 1rem;
            }

            .table-modern thead {
                display: none;
            }

            .table-modern tbody tr {
                display: block;
                margin-bottom: 1rem;
                border-radius: 0.75rem;
                box-shadow: var(--shadow-sm);
            }

            .table-modern tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem 1rem;
                border-bottom: 1px solid #f1f5f9;
            }

            .table-modern tbody td:before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--dark-color);
            }

            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>




<div class="container">
    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="products.php" class="filter-tab <?= !$view_deleted ? 'active' : '' ?>">
            <i class="bi bi-list-ul me-2"></i>Active Products
        </a>
        <a href="products.php?view_deleted=1" class="filter-tab <?= $view_deleted ? 'active' : '' ?>">
            <i class="bi bi-trash me-2"></i>Deleted Products
        </a>
    </div>

    <!-- Main Content Card -->
    <div class="main-card">
        <div class="card-header-modern">
            <h3 class="card-title-modern">
                <?= $view_deleted ? 'Deleted' : 'Active' ?> Product List
            </h3>
            <div class="search-container">
                <input type="text" class="form-control search-input" placeholder="Search Products..." id="searchInput">
                <i class="bi bi-search search-icon"></i>
            </div>
            <?php if (!$view_deleted): ?>
                <button type="button" class="btn btn-primary-modern btn-modern" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="bi bi-plus-circle me-2"></i>Add New Product
                </button>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi <?= $view_deleted ? 'bi-archive' : 'bi-box-seam' ?>"></i>
                    </div>
                    <h4>No <?= $view_deleted ? 'deleted' : 'active' ?> products found</h4>
                    <p class="text-muted">
                        <?= $view_deleted
                            ? 'No products have been deleted yet.'
                            : 'Start by adding your first product to the inventory.'
                        ?>
                    </p>
                    <?php if (!$view_deleted): ?>
                        <button type="button" class="btn btn-primary-modern btn-modern mt-3" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="bi bi-plus-circle me-2"></i>Add Your First Product
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table id="productsTable" class="table table-modern">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Product Details</th>
                            <th>Category</th>
                            <th>Pricing</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $prod): ?>
                            <tr id="product-row-<?= $prod['id'] ?>">
                                <td data-label="ID">
                                    <span class="fw-bold">#<?= esc_html($prod['id']) ?></span>
                                </td>
                                <td data-label="Image">
                                    <img src="assets/uploads/<?= esc_html($prod['image'] ?? 'default.png') ?>"
                                        alt="<?= esc_html($prod['name']) ?>"
                                        class="product-image">
                                </td>
                                <td data-label="Product">
                                    <div>
                                        <div class="fw-bold product-name"><?= esc_html($prod['name']) ?></div>
                                        <?php if (!empty($prod['short_description'])): ?>
                                            <div class="text-muted small mt-1"><?= esc_html($prod['short_description']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="Category">
                                    <span class="badge badge-modern badge-category">
                                        <?= esc_html($prod['category_name']) ?>
                                    </span>
                                </td>
                                <td data-label="Pricing">
                                    <div class="price-display">
                                        <div class="selling-price">৳<?= number_format($prod['price'], 2) ?></div>
                                        <div class="cost-price">Cost: ৳<?= number_format($prod['cost_price'], 2) ?></div>
                                    </div>
                                </td>
                                <td data-label="Stock">
                                    <?php
                                    $stock_class = 'stock-good';
                                    if ($prod['stock'] <= 5) $stock_class = 'stock-low';
                                    elseif ($prod['stock'] <= 20) $stock_class = 'stock-medium';
                                    ?>
                                    <span class="stock-indicator <?= $stock_class ?>">
                                        <?= esc_html($prod['stock']) ?> units
                                    </span>
                                </td>
                                <td data-label="Status">
                                    <?php if (!$view_deleted): ?>
                                        <label class="toggle-switch">
                                            <input type="checkbox" class="status-toggle"
                                                data-id="<?= $prod['id'] ?>"
                                                <?= $prod['is_active'] ? 'checked' : '' ?>>
                                            <span class="slider"></span>
                                        </label>
                                    <?php else: ?>
                                        <span class="badge badge-modern badge-deleted">Deleted</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Actions">
                                    <div class="action-buttons">
                                        <?php if ($view_deleted): ?>
                                            <button type="button" class="btn btn-action btn-restore restore-product-btn"
                                                data-id="<?= htmlspecialchars($prod['id']) ?>">
                                                <i class="bi bi-arrow-clockwise me-1"></i>Restore
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-action btn-images manage-images-btn"
                                                data-bs-toggle="modal" data-bs-target="#manageImagesModal"
                                                data-id="<?= htmlspecialchars($prod['id']) ?>">
                                                <i class="bi bi-images me-1"></i>Images
                                            </button>
                                            <button type="button" class="btn btn-action btn-edit edit-product-btn"
                                                data-bs-toggle="modal" data-bs-target="#editProductModal"
                                                data-id="<?= $prod['id'] ?>"
                                                data-name="<?= esc_html($prod['name']) ?>"
                                                data-category-id="<?= $prod['category_id'] ?>"
                                                data-description="<?= esc_html($prod['description']) ?>"
                                                data-short-description="<?= esc_html($prod['short_description']) ?>"
                                                data-price="<?= $prod['price'] ?>"
                                                data-cost-price="<?= $prod['cost_price'] ?>"
                                                data-stock="<?= $prod['stock'] ?>">
                                                <i class="bi bi-pencil me-1"></i>Edit
                                            </button>
                                            <button type="button" class="btn btn-action btn-delete delete-product-btn"
                                                data-id="<?= htmlspecialchars($prod['id']) ?>">
                                                <i class="bi bi-trash me-1"></i>Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-modern">
                    <nav>
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link-modern" href="?page=<?= $page - 1 ?><?= $view_deleted ? '&view_deleted=1' : '' ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link-modern" href="?page=<?= $i ?><?= $view_deleted ? '&view_deleted=1' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link-modern" href="?page=<?= $page + 1 ?><?= $view_deleted ? '&view_deleted=1' : '' ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade modal-modern" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Add New Product
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="add_product_name" class="form-label form-label-modern">Product Name *</label>
                            <input type="text" name="name" id="add_product_name" required class="form-control form-control-modern">
                        </div>
                        <div class="col-md-6">
                            <label for="add_product_category" class="form-label form-label-modern">Category *</label>
                            <select name="category_id" id="add_product_category" required class="form-select form-control-modern">
                                <option value="" disabled selected>Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= esc_html($cat['id']) ?>"><?= esc_html($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="add_product_short_description" class="form-label form-label-modern">Short Description</label>
                            <input type="text" name="short_description" id="add_product_short_description"
                                class="form-control form-control-modern" maxlength="255"
                                placeholder="A brief, one-line summary of the product">
                            <div class="form-text text-muted">Optional: Brief product summary for quick overview</div>
                        </div>
                        <div class="col-12">
                            <label for="add_product_description" class="form-label form-label-modern">Full Description *</label>
                            <textarea name="description" id="add_product_description" required
                                class="form-control form-control-modern" rows="4"
                                placeholder="Detailed product description..."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="add_product_cost_price" class="form-label form-label-modern">Cost Price *</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" name="cost_price" id="add_product_cost_price" required
                                    class="form-control form-control-modern" step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="add_product_price" class="form-label form-label-modern">Selling Price *</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" name="price" id="add_product_price" required
                                    class="form-control form-control-modern" step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="add_product_stock" class="form-label form-label-modern">Initial Stock *</label>
                            <input type="number" name="stock" id="add_product_stock" required
                                class="form-control form-control-modern" min="0" placeholder="0">
                        </div>
                        <div class="col-md-6">
                            <label for="add_product_image" class="form-label form-label-modern">Main Image *</label>
                            <input type="file" name="image" id="add_product_image"
                                class="form-control form-control-modern" required accept="image/*">
                            <div class="form-text text-muted">This will be the primary product image</div>
                        </div>
                        <div class="col-md-6">
                            <label for="add_additional_images" class="form-label form-label-modern">Additional Images</label>
                            <input type="file" name="additional_images[]" id="add_additional_images"
                                class="form-control form-control-modern" multiple accept="image/*">
                            <div class="form-text text-muted">Optional: Upload multiple images</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-modern btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" name="add_product" class="btn btn-primary-modern btn-modern">
                        <i class="bi bi-check-circle me-2"></i>Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade modal-modern" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProductModalLabel">
                        <i class="bi bi-pencil me-2"></i>Edit Product
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="edit_product_name" class="form-label form-label-modern">Product Name *</label>
                            <input type="text" name="name" id="edit_product_name" required class="form-control form-control-modern">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_product_category" class="form-label form-label-modern">Category *</label>
                            <select name="category_id" id="edit_product_category" required class="form-select form-control-modern">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= esc_html($cat['id']) ?>"><?= esc_html($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="edit_product_short_description" class="form-label form-label-modern">Short Description</label>
                            <input type="text" name="short_description" id="edit_product_short_description"
                                class="form-control form-control-modern" maxlength="255">
                        </div>
                        <div class="col-12">
                            <label for="edit_product_description" class="form-label form-label-modern">Full Description *</label>
                            <textarea name="description" id="edit_product_description" required
                                class="form-control form-control-modern" rows="4"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_product_cost_price" class="form-label form-label-modern">Cost Price *</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" name="cost_price" id="edit_product_cost_price" required
                                    class="form-control form-control-modern" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_product_price" class="form-label form-label-modern">Selling Price *</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" name="price" id="edit_product_price" required
                                    class="form-control form-control-modern" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="edit_product_stock" class="form-label form-label-modern">Current Stock *</label>
                            <input type="number" name="stock" id="edit_product_stock" required
                                class="form-control form-control-modern" min="0">
                        </div>
                        <div class="col-md-6">
                            <label for="edit_product_image" class="form-label form-label-modern">Change Main Image</label>
                            <input type="file" name="image" id="edit_product_image"
                                class="form-control form-control-modern" accept="image/*">
                            <div class="form-text text-muted">Leave blank to keep current image</div>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_additional_images" class="form-label form-label-modern">Add More Images</label>
                            <input type="file" name="additional_images[]" id="edit_additional_images"
                                class="form-control form-control-modern" multiple accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-modern btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" name="update_product" class="btn btn-primary-modern btn-modern">
                        <i class="bi bi-check-circle me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Manage Images Modal -->
<div class="modal fade modal-modern" id="manageImagesModal" tabindex="-1" aria-labelledby="manageImagesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manageImagesModalLabel">
                    <i class="bi bi-images me-2"></i>Manage Product Images
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="image-gallery-container" class="row g-3">
                    <!-- Images will be loaded here dynamically -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-modern btn-modern" data-bs-dismiss="modal">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Edit Product Modal Handler
        const editProductModal = document.getElementById('editProductModal');
        if (editProductModal) {
            editProductModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const modal = this;

                // Get data from button attributes
                const id = button.dataset.id;
                const name = button.dataset.name;
                const shortDescription = button.dataset.shortDescription;
                const categoryId = button.dataset.categoryId;
                const description = button.dataset.description;
                const price = button.dataset.price;
                const costPrice = button.dataset.costPrice;
                const stock = button.dataset.stock;

                // Populate form fields
                modal.querySelector('#edit_product_id').value = id;
                modal.querySelector('#edit_product_name').value = name;
                modal.querySelector('#edit_product_short_description').value = shortDescription;
                modal.querySelector('#edit_product_category').value = categoryId;
                modal.querySelector('#edit_product_description').value = description;
                modal.querySelector('#edit_product_price').value = price;
                modal.querySelector('#edit_product_cost_price').value = costPrice;
                modal.querySelector('#edit_product_stock').value = stock;
            });
        }

        // Image Management
        const productsTable = document.getElementById('productsTable');
        const manageImagesModalEl = document.getElementById('manageImagesModal');

        // Function to fetch and render images
        function fetchAndRenderImages(productId) {
            const imageGalleryContainer = document.getElementById('image-gallery-container');
            imageGalleryContainer.innerHTML = `
                    <div class="col-12 text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading images...</p>
                    </div>`;

            fetch(`ajax/fetch_product_images.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        imageGalleryContainer.innerHTML = '';
                        if (data.images.length === 0) {
                            imageGalleryContainer.innerHTML = `
                                    <div class="col-12 text-center p-5">
                                        <i class="bi bi-images" style="font-size: 3rem; opacity: 0.3;"></i>
                                        <p class="mt-2 text-muted">No images found for this product.</p>
                                    </div>`;
                            return;
                        }

                        data.images.forEach(img => {
                            const col = document.createElement('div');
                            col.className = 'col-md-4 col-lg-3';
                            col.id = `image-card-${img.id}`;

                            let badge = img.is_main ? '<span class="badge bg-primary position-absolute top-0 start-0 m-2">Main</span>' : '';
                            let setMainBtn = !img.is_main ? `<button class="btn btn-sm btn-outline-success set-main-btn" data-image-path="${img.path}">Set as Main</button>` : '';
                            let deleteBtn = !img.is_main ? `<button class="btn btn-sm btn-outline-danger delete-image-btn" data-image-id="${img.id}">Delete</button>` : '<span class="text-muted small d-block mt-2">Cannot delete main image</span>';

                            col.innerHTML = `
                                    <div class="card h-100 position-relative" style="border-radius: 1rem; overflow: hidden; box-shadow: var(--shadow-md);">
                                        <img src="assets/uploads/${img.path}" class="card-img-top" style="height: 200px; object-fit: cover;">
                                        ${badge}
                                        <div class="card-body text-center">
                                            <div class="d-grid gap-2">
                                                ${setMainBtn}
                                                ${deleteBtn}
                                            </div>
                                        </div>
                                    </div>`;
                            imageGalleryContainer.appendChild(col);
                        });
                    } else {
                        imageGalleryContainer.innerHTML = `<div class="col-12"><div class="alert alert-danger">${data.message}</div></div>`;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    imageGalleryContainer.innerHTML = '<div class="col-12"><div class="alert alert-danger">Failed to load images.</div></div>';
                });
        }

        // Handle manage images modal
        if (manageImagesModalEl) {
            manageImagesModalEl.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const productId = button.dataset.id;
                this.dataset.productId = productId;
                fetchAndRenderImages(productId);
            });

            // Event delegation for image management buttons
            document.getElementById('image-gallery-container').addEventListener('click', function(e) {
                const productId = manageImagesModalEl.dataset.productId;

                // Handle Set as Main
                if (e.target.classList.contains('set-main-btn')) {
                    const imagePath = e.target.dataset.imagePath;
                    if (confirm('Set this as the main image? The current main image will become an additional image.')) {
                        fetch('ajax/set_main_image.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `product_id=${productId}&image_path=${imagePath}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    fetchAndRenderImages(productId);
                                } else {
                                    alert(`Error: ${data.message}`);
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    }
                }

                // Handle Delete Image
                if (e.target.classList.contains('delete-image-btn')) {
                    const imageId = e.target.dataset.imageId;
                    if (confirm('Permanently delete this image?')) {
                        fetch('ajax/delete_product_image.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `image_id=${imageId}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    document.getElementById(`image-card-${imageId}`).remove();
                                } else {
                                    alert(`Error: ${data.message}`);
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    }
                }
            });
        }

        // Handle product actions
        if (productsTable) {
            productsTable.addEventListener('click', function(e) {
                const target = e.target.closest('button');
                if (!target) return;

                const productId = target.dataset.id;

                // Delete product
                if (target.classList.contains('delete-product-btn')) {
                    if (confirm('Are you sure you want to delete this product?')) {
                        fetch('ajax/soft_delete_product.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `id=${productId}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    document.getElementById(`product-row-${productId}`).remove();
                                } else {
                                    alert(`Error: ${data.message}`);
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    }
                }

                // Restore product
                if (target.classList.contains('restore-product-btn')) {
                    if (confirm('Are you sure you want to restore this product?')) {
                        fetch('ajax/restore_product.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `id=${productId}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    document.getElementById(`product-row-${productId}`).remove();
                                } else {
                                    alert(`Error: ${data.message}`);
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    }
                }
            });

            // Handle status toggle
            productsTable.addEventListener('change', function(e) {
                if (e.target.classList.contains('status-toggle')) {
                    const toggle = e.target;
                    const productId = toggle.dataset.id;
                    const newStatus = toggle.checked ? 1 : 0;

                    fetch('ajax/toggle_product_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `id=${productId}&status=${newStatus}`
                        })
                        .then(response => response.json())
                        .catch(error => console.error('Error:', error));
                }
            });
        }

        // Add smooth transitions and animations
        const cards = document.querySelectorAll('.main-card, .stat-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
            card.classList.add('animate__animated', 'animate__fadeInUp');
        });

        // Add hover effects to buttons
        const buttons = document.querySelectorAll('.btn-modern');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.02)';
            });
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    });

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#productsTableBody tr');

        rows.forEach(row => {
            const categoryNameCell = row.querySelector('.product-name');
            if (categoryNameCell) {
                const categoryName = categoryNameCell.textContent.toLowerCase();
                row.style.display = categoryName.includes(searchTerm) ? '' : 'none';
            }
        });
    });
</script>


<?php include 'includes/footer.php'; ?>
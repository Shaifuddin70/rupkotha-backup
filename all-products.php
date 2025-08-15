<?php
// all-products.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// The header file already starts the session and includes db.php and functions.php
include 'includes/header.php';

// --- DATA FETCHING ---

// Fetch all categories for the filter sidebar
$categories_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Filtering & Pagination Logic ---
$selected_category_id = filter_input(INPUT_GET, 'category', FILTER_VALIDATE_INT);
$sort_by = filter_input(INPUT_GET, 'sort', FILTER_UNSAFE_RAW) ?: 'newest';
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$per_page = 12; // 12 products per page
$offset = ($page - 1) * $per_page;

// Build the base query and parameters using only named placeholders
$where_clauses = ["is_active = 1"];
$params = [];

if ($selected_category_id) {
    $where_clauses[] = "category_id = :category_id";
    $params[':category_id'] = $selected_category_id;
}

$where_sql = " WHERE " . implode(" AND ", $where_clauses);

// Determine sort order
$order_by = "created_at DESC"; // Default
switch ($sort_by) {
    case 'price_low':
        $order_by = "price ASC";
        break;
    case 'price_high':
        $order_by = "price DESC";
        break;
    case 'name':
        $order_by = "name ASC";
        break;
    case 'oldest':
        $order_by = "created_at ASC";
        break;
    default:
        $order_by = "created_at DESC";
}

// Get total number of products for pagination with filter
$total_products_stmt = $pdo->prepare("SELECT COUNT(id) FROM products" . $where_sql);
$total_products_stmt->execute($params);
$total_products = $total_products_stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Fetch the paginated products with filter
$products_sql = "SELECT * FROM products" . $where_sql . " ORDER BY " . $order_by . " LIMIT :limit OFFSET :offset";
$products_stmt = $pdo->prepare($products_sql);

// Combine all named parameters and bind them
$all_params = array_merge(
    $params,
    [':limit' => $per_page, ':offset' => $offset]
);

// Bind all parameters. We must bind LIMIT/OFFSET as integers.
foreach ($all_params as $key => &$val) {
    if ($key === ':limit' || $key === ':offset') {
        $products_stmt->bindParam($key, $val, PDO::PARAM_INT);
    } else {
        $products_stmt->bindParam($key, $val);
    }
}

$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

?>



<!-- Hero Section -->
<section class="products-hero">
    <div class="container text-center">
        <h1 class="fade-in text-uppercase">Discover Amazing Products</h1>
    </div>
</section>

<!-- Mobile Filters (visible on mobile) -->
<div class="container d-lg-none">
    <div class="filters-section fade-in">
        <div class="row g-3">
            <div class="col-6">
                <form method="get" action="all-products.php" id="mobileFilterForm">
                    <select name="category" class="form-select custom-filter-select" onchange="document.getElementById('mobileFilterForm').submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= ($selected_category_id == $category['id']) ? 'selected' : '' ?>>
                                <?= esc_html($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="col-6">
                <form method="get" action="all-products.php" id="mobileSortForm">
                    <?php if ($selected_category_id): ?>
                        <input type="hidden" name="category" value="<?= $selected_category_id ?>">
                    <?php endif; ?>
                    <select name="sort" class="form-select custom-filter-select" onchange="document.getElementById('mobileSortForm').submit()">
                        <option value="newest" <?= ($sort_by === 'newest') ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest" <?= ($sort_by === 'oldest') ? 'selected' : '' ?>>Oldest First</option>
                        <option value="price_low" <?= ($sort_by === 'price_low') ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high" <?= ($sort_by === 'price_high') ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="name" <?= ($sort_by === 'name') ? 'selected' : '' ?>>Name: A to Z</option>
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>

<main class="container my-5">
    <div class="row">
        <!-- Desktop Sidebar -->
        <aside class="col-lg-3 d-none d-lg-block">
            <div class="custom-sidebar fade-in">
                <h4 class="sidebar-title">
                    <i class="bi bi-funnel me-2"></i>Filters
                </h4>

                <div class="mb-4">
                    <label class="form-label fw-bold mb-3">Category</label>
                    <form method="get" action="all-products.php" id="categoryFilterForm">
                        <select name="category" class="form-select custom-filter-select" onchange="document.getElementById('categoryFilterForm').submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= ($selected_category_id == $category['id']) ? 'selected' : '' ?>>
                                    <?= esc_html($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold mb-3">Sort By</label>
                    <form method="get" action="all-products.php" id="sortForm">
                        <?php if ($selected_category_id): ?>
                            <input type="hidden" name="category" value="<?= $selected_category_id ?>">
                        <?php endif; ?>
                        <select name="sort" class="form-select custom-filter-select" onchange="document.getElementById('sortForm').submit()">
                            <option value="newest" <?= ($sort_by === 'newest') ? 'selected' : '' ?>>Newest First</option>
                            <option value="oldest" <?= ($sort_by === 'oldest') ? 'selected' : '' ?>>Oldest First</option>
                            <option value="price_low" <?= ($sort_by === 'price_low') ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price_high" <?= ($sort_by === 'price_high') ? 'selected' : '' ?>>Price: High to Low</option>
                            <option value="name" <?= ($sort_by === 'name') ? 'selected' : '' ?>>Name: A to Z</option>
                        </select>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Products Grid -->
        <section class="col-lg-9">
            <!-- Results Header -->
            <div class="results-header fade-in">
                <div class="results-count">
                    <strong><?= number_format($total_products) ?></strong>
                    <?= $total_products === 1 ? 'product' : 'products' ?> found
                    <?php if ($selected_category_id): ?>
                        <?php
                        $selected_cat = array_filter($categories, fn($c) => $c['id'] == $selected_category_id);
                        $selected_cat = reset($selected_cat);
                        ?>
                        in <strong><?= esc_html($selected_cat['name']) ?></strong>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4 fade-in" id="productsGrid">
                <?php if (empty($products)): ?>
                    <div class="col-12">
                        <div class="empty-state">
                            <div class="empty-state-icon">
                                <i class="bi bi-search"></i>
                            </div>
                            <h3>No Products Found</h3>
                            <p>We couldn't find any products matching your current selection. Try adjusting your filters or browse all categories.</p>
                            <a href="all-products.php" class="add-to-cart-btn d-inline-flex">
                                <i class="bi bi-arrow-left me-2"></i>View All Products
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-lg-3 col-md-6">
                            <div class="custom-product-card">
                                <div class="product-image-container">
                                    <?php
                                    $isNew = (strtotime($product['created_at'] ?? 'now') >= strtotime('-14 days'));
                                    $lowStock = ($product['stock'] ?? 0) > 0 && ($product['stock'] ?? 0) <= 5;
                                    ?>
                                    <?php if ($isNew): ?><span class="product-badge badge-new">New</span><?php endif; ?>
                                    <?php if ($lowStock): ?><span class="product-badge badge-low" style="left:auto; right:10px;">Low</span><?php endif; ?>

                                    <div class="price-badge"><?= formatPrice($product['price']) ?></div>
                                    <img src="admin/assets/uploads/<?= esc_html($product['image']) ?>"
                                        class="product-image"
                                        alt="<?= esc_html($product['name']) ?>"
                                        loading="lazy">
                                    <div class="product-overlay">
                                        <a href="product.php?id=<?= $product['id'] ?>" class="quick-view-btn">
                                            <i class="bi bi-eye"></i>Quick View
                                        </a>
                                    </div>
                                </div>
                                <div class="product-info">
                                    <a href="product.php?id=<?= $product['id'] ?>" class="product-name">
                                        <?= esc_html($product['name']) ?>
                                    </a>
                                    <div class="product-price">
                                        <?= formatPrice($product['price']) ?>
                                    </div>
                                    <?php if (($product['stock'] ?? 0) > 0): ?>
                                        <div class="button-container">
                                            <a href="product.php?id=<?= $product['id'] ?>" class="buy-button button">Buy Now</a>
                                            <a href="add_to_cart.php?id=<?= $product['id'] ?>" class="cart-button button" aria-label="Add to cart">
                                                <svg viewBox="0 0 27.97 25.074" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M0,1.175A1.173,1.173,0,0,1,1.175,0H3.4A2.743,2.743,0,0,1,5.882,1.567H26.01A1.958,1.958,0,0,1,27.9,4.035l-2.008,7.459a3.532,3.532,0,0,1-3.4,2.61H8.36l.264,1.4a1.18,1.18,0,0,0,1.156.955H23.9a1.175,1.175,0,0,1,0,2.351H9.78a3.522,3.522,0,0,1-3.462-2.865L3.791,2.669A.39.39,0,0,0,3.4,2.351H1.175A1.173,1.173,0,0,1,0,1.175ZM6.269,22.724a2.351,2.351,0,1,1,2.351,2.351A2.351,2.351,0,0,1,6.269,22.724Zm16.455-2.351a2.351,2.351,0,1,1-2.351,2.351A2.351,2.351,0,0,1,22.724,20.373Z"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <button class="btn out-of-stock-custom" disabled>Out of Stock</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="custom-pagination fade-in">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php
                            $pagination_params = [];
                            if ($selected_category_id) {
                                $pagination_params['category'] = $selected_category_id;
                            }
                            if ($sort_by !== 'newest') {
                                $pagination_params['sort'] = $sort_by;
                            }
                            ?>
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&<?= http_build_query($pagination_params) ?>" aria-label="Previous">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            if ($start_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1&<?= http_build_query($pagination_params) ?>">1</a>
                                </li>
                                <?php if ($start_page > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query($pagination_params) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $total_pages ?>&<?= http_build_query($pagination_params) ?>"><?= $total_pages ?></a>
                                </li>
                            <?php endif; ?>

                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&<?= http_build_query($pagination_params) ?>" aria-label="Next">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<script>
    // Add loading animation when filtering
    document.querySelectorAll('form select').forEach(select => {
        select.addEventListener('change', function() {
            const grid = document.getElementById('productsGrid');
            if (grid) {
                grid.style.opacity = '0.6';
                grid.style.pointerEvents = 'none';
            }
        });
    });

    // Lazy loading for images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    img.classList.remove('loading-skeleton');
                    observer.unobserve(img);
                }
            });
        });

        document.querySelectorAll('.product-image').forEach(img => {
            imageObserver.observe(img);
        });
    }

    // Add to cart with loading state
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (this.href && this.href.includes('add_to_cart.php')) {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Adding...';
                this.style.pointerEvents = 'none';

                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.pointerEvents = 'auto';
                }, 2000);
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
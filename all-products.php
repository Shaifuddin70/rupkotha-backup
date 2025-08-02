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

// Get total number of products for pagination with filter
$total_products_stmt = $pdo->prepare("SELECT COUNT(id) FROM products" . $where_sql);
$total_products_stmt->execute($params);
$total_products = $total_products_stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Fetch the paginated products with filter
$products_sql = "SELECT * FROM products" . $where_sql . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$products_stmt = $pdo->prepare($products_sql);

// Combine all named parameters and bind them
$all_params = array_merge(
    $params,
    [':limit' => $per_page, ':offset' => $offset]
);

// Bind all parameters. We must bind LIMIT/OFFSET as integers.
foreach ($all_params as $key => &$val) {
    // Use bindParam for the loop variable, and bindValue for static values if preferred
    if ($key === ':limit' || $key === ':offset') {
        $products_stmt->bindParam($key, $val, PDO::PARAM_INT);
    } else {
        $products_stmt->bindParam($key, $val);
    }
}

$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
    .page-header {
        background-color: #f8f9fa;
        padding: 2rem 0;
        border-bottom: 1px solid #e9ecef;
    }

    .category-sidebar .form-select {
        font-size: 0.95rem;
    }

    .product-card {
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.08);
    }

    .product-card .card-img-top {
        aspect-ratio: 1 / 1;
        object-fit: cover;
    }

    .product-card .card-title a {
        color: #343a40;
        text-decoration: none;
        transition: color 0.2s;
    }

    .product-card .card-title a:hover {
        color: #0d6efd;
    }

    .product-card .card-footer {
        background-color: #fff;
        border-top: 1px solid #e9ecef;
        padding: 0.75rem 1.25rem;
    }

    .product-card .btn-add-to-cart {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
</style>



<main class="container my-5">
    <div class="row">
        <!-- Category Filter Sidebar -->
        <aside class="col-lg-3 mb-4 mb-lg-0">
            <div class="category-sidebar">
                <h4 class="h5 mb-3 fw-bold">Categories</h4>
                <form method="get" action="all-products.php" id="categoryFilterForm">
                    <select name="category" class="form-select" onchange="document.getElementById('categoryFilterForm').submit()">
                        <option value="">All Products</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= ($selected_category_id == $category['id']) ? 'selected' : '' ?>>
                                <?= esc_html($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </aside>

        <!-- Products Grid -->
        <section class="col-lg-9">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-2 row-cols-lg-3 g-4">
                <?php if (empty($products)): ?>
                    <div class="col-12">
                        <div class="alert alert-light text-center" role="alert">
                            <h4 class="alert-heading">No Products Found</h4>
                            <p class="mb-0">There are no products matching your current selection. Please try a different category.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col">
                            <div class="card h-100 product-card">
                                <a href="product.php?id=<?= $product['id'] ?>">
                                    <img src="admin/assets/uploads/<?= esc_html($product['image']) ?>" class="card-img-top" alt="<?= esc_html($product['name']) ?>">
                                </a>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title h6"><a href="product.php?id=<?= $product['id'] ?>"><?= esc_html($product['name']) ?></a></h5>
                                    <p class="card-text fs-5 fw-bold text-primary mb-0 mt-auto"><?= formatPrice($product['price']) ?></p>
                                </div>
                                <div class="card-footer">
                                    <a href="add_to_cart.php?id=<?= $product['id'] ?>" class="btn btn-primary w-100 btn-add-to-cart">
                                        <i class="bi bi-cart-plus"></i> Add to Cart
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php
                        $pagination_params = [];
                        if ($selected_category_id) {
                            $pagination_params['category'] = $selected_category_id;
                        }
                        ?>
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&<?= http_build_query($pagination_params) ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query($pagination_params) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&<?= http_build_query($pagination_params) ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
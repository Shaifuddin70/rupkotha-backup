<?php
// This is your "All Products" listing page, e.g., all-products.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// The header file already starts the session and includes db.php and functions.php
include 'includes/header.php';

// --- DATA FETCHING FOR THE ALL PRODUCTS PAGE ---

// 1. All Products with Pagination
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$per_page = 12; // 12 products per page
$offset = ($page - 1) * $per_page;

// Get total number of all products for pagination
$total_products = $pdo->query("SELECT COUNT(id) FROM products WHERE is_active = 1")->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Fetch the paginated products
$products_stmt = $pdo->prepare(
    "SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
);
$products_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$products_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

?>



<div class="page-header mt-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">All Products</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold mt-2">All Products</h1>
    </div>
</div>

<main class="container my-5">
    <section class="all-products-grid">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php if (empty($products)): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        There are currently no products to display.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="col">
                        <div class="card h-100 product-card">
                            <a href="product.php?id=<?= $product['id'] ?>">
                                <img src="admin/assets/uploads/<?= esc_html($product['image']) ?>" class="card-img-top product-card-img-top" alt="<?= esc_html($product['name']) ?>">
                            </a>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title h6"><a href="product.php?id=<?= $product['id'] ?>" class="text-dark text-decoration-none"><?= esc_html($product['name']) ?></a></h5>
                                <p class="card-text fw-bold text-primary mb-0"><?= formatPrice($product['price']) ?></p>
                            </div>
                            <div class="card-footer bg-transparent border-top-0">
                                <a href="add_to_cart.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary w-100">Add to Cart</a>
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
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
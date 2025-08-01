<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// --- Get Search Query ---
$search_query = trim($_GET['q'] ?? '');

// --- PAGINATION SETUP ---
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$per_page = 12; // 4 products per row
$offset = ($page - 1) * $per_page;

$products = [];
$total_products = 0;

if (!empty($search_query)) {
    // --- DATA FETCHING ---
    // Prepare the search term for a LIKE query
    $search_term = '%' . $search_query . '%';

    // Get total count for pagination
    $count_sql = "SELECT COUNT(id) FROM products WHERE (name LIKE ? OR description LIKE ?) AND is_active = 1 AND deleted_at IS NULL";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute([$search_term, $search_term]);
    $total_products = $count_stmt->fetchColumn();
    $total_pages = ceil($total_products / $per_page);

    // Fetch the products for the current page
    $sql = "SELECT * FROM products 
            WHERE (name LIKE ? OR description LIKE ?) 
            AND is_active = 1 AND deleted_at IS NULL
            ORDER BY name ASC 
            LIMIT {$per_page} OFFSET {$offset}";
    $products_stmt = $pdo->prepare($sql);
    $products_stmt->execute([$search_term, $search_term]);
    $products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<div class="container my-5">
    <div class="page-header mb-4">
        <h1 class="display-5">Search Results</h1>
        <?php if (!empty($search_query)): ?>
            <p class="lead text-muted">Showing results for: "<strong><?= esc_html($search_query) ?></strong>"</p>
        <?php endif; ?>
    </div>

    <?php if (empty($search_query)): ?>
        <div class="text-center p-5 bg-light rounded">
            <p>Please enter a search term to find products.</p>
        </div>
    <?php elseif (empty($products)): ?>
        <div class="text-center p-5 bg-light rounded">
            <h4>No Products Found</h4>
            <p>We couldn't find any products matching your search. Try a different keyword.</p>
            <a href="index.php" class="btn btn-primary">Back to Home</a>
        </div>
    <?php else: ?>
        <!-- Product Grid -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <div class="card h-100 product-card">
                        <a href="product.php?id=<?= $product['id'] ?>">
                            <img src="admin/assets/uploads/<?= esc_html($product['image']) ?>"
                                 class="card-img-top product-card-img-top" alt="<?= esc_html($product['name']) ?>">
                        </a>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title h6"><a href="product.php?id=<?= $product['id'] ?>"
                                                         class="text-dark text-decoration-none"><?= esc_html($product['name']) ?></a>
                            </h5>
                            <p class="card-text fw-bold text-primary mb-0"><?= formatPrice($product['price']) ?></p>
                        </div>
                        <div class="card-footer bg-transparent border-top-0">
                            <a href="add_to_cart.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary w-100">Add
                                to Cart</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-5">
                <ul class="pagination justify-content-center">
                    <?php
                    $filter_params = http_build_query(['q' => $search_query]);
                    ?>
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link"
                           href="?page=<?= $page - 1 ?>&<?= $filter_params ?>"><span>&laquo;</span></a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&<?= $filter_params ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link"
                           href="?page=<?= $page + 1 ?>&<?= $filter_params ?>"><span>&raquo;</span></a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

    <?php endif; ?>
</div>


<?php require_once 'includes/footer.php'; ?>

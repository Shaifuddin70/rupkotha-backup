<?php
// This is your main storefront page, e.g., index.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// The header file already starts the session and includes db.php and functions.php
include 'includes/header.php';

// --- DATA FETCHING FOR THE HOMEPAGE ---

// 1. Hero Slider Products
$hero_stmt = $pdo->query("SELECT * FROM hero_products WHERE is_active = 1 ORDER BY id DESC LIMIT 5");
$hero_products = $hero_stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Top Selling Products - MODIFIED to include the stock level
$top_selling_stmt = $pdo->query(
    "SELECT p.id, p.name, p.price, p.image, p.stock, SUM(oi.quantity) as total_sold
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     GROUP BY p.id, p.name, p.price, p.image, p.stock
     ORDER BY total_sold DESC
     LIMIT 4"
);
$top_selling_products = $top_selling_stmt->fetchAll(PDO::FETCH_ASSOC);


// 3. All Products with Pagination
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$per_page = 4; // 8 products per page
$offset = ($page - 1) * $per_page;

// Get total number of products for pagination calculation
$total_products = $pdo->query("SELECT COUNT(id) FROM products WHERE is_active = 1")->fetchColumn();
$total_pages = ceil($total_products / $per_page);
// 2. Fetch New Arrivals (e.g., the 8 most recent products)
$new_arrivals_stmt = $pdo->query(
    "SELECT * FROM products 
     WHERE is_active = 1 AND deleted_at IS NULL 
     ORDER BY created_at DESC 
     LIMIT 4"
);
$new_arrivals = $new_arrivals_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch the products for the current page (SELECT * already includes stock)
$products_stmt = $pdo->prepare(
    "SELECT * FROM products WHERE is_active = 1 ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
);
$products_stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$products_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<!-- Hero Section with Slider -->
<?php if (!empty($hero_products)): ?>
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <?php foreach ($hero_products as $index => $item): ?>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $index ?>"
                    class="<?= $index === 0 ? 'active' : '' ?>" aria-current="true"
                    aria-label="Slide <?= $index + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
        <div class="carousel-inner">
            <?php foreach ($hero_products as $index => $item): ?>
                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                    <div class="hero-slide"
                        style="background-image: url('admin/assets/uploads/<?= esc_html($item['image']) ?>');">
                        <div class="hero-overlay"></div>
                        <div class="container">
                            <div class="carousel-caption text-start">
                                <h1 class="display-4 fw-bold"><?= esc_html($item['title']) ?></h1>
                                <p class="lead col-lg-8"><?= esc_html($item['subtitle']) ?></p>
                                <p><a class="btn btn-lg btn-primary" href="product?id=<?= $item['product_id'] ?>">Shop
                                        Now</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>

<?php endif; ?>


<main class="container my-5">
    <section class="mb-5">
        <h2 class="section-title text-center mb-4">New Arrivals</h2>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($new_arrivals as $product): ?>
                <div class="col">
                    <div class="card h-100 product-card">
                        <a href="product.php?id=<?= $product['id'] ?>">
                            <img src="admin/assets/uploads/<?= esc_html($product['image']) ?>"
                                class="card-img-top product-card-img-top" alt="<?= esc_html($product['name']) ?>">
                        </a>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title h6"><a href="product?id=<?= $product['id'] ?>"
                                    class="text-dark text-decoration-none"><?= esc_html($product['name']) ?></a>
                            </h5>
                            <p class="card-text fw-bold text-primary mb-0"><?= formatPrice($product['price']) ?></p>
                        </div>
                        <div class="card-footer bg-transparent border-top-0">
                            <?php if ($product['stock'] > 0): ?>
                                <a href="add_to_cart?id=<?= $product['id'] ?>"
                                    class="btn btn-outline-primary w-100">Add to Cart</a>
                            <?php else: ?>
                                <button class="btn btn-outline-danger w-100" disabled>Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <!-- Top Selling Products Section -->
    <?php if (!empty($top_selling_products)): ?>
        <section class="top-selling mb-5">
            <h2 class="text-center section-title">Top Selling Products</h2>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                <?php foreach ($top_selling_products as $product): ?>
                    <div class="col">
                        <div class="card h-100 product-card">
                            <a href="product?id=<?= $product['id'] ?>">
                                <img src="admin/assets/uploads/<?= esc_html($product['image']) ?>"
                                    class="card-img-top product-card-img-top" alt="<?= esc_html($product['name']) ?>">
                            </a>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title h6"><a href="product?id=<?= $product['id'] ?>"
                                        class="text-dark text-decoration-none"><?= esc_html($product['name']) ?></a>
                                </h5>
                                <p class="card-text fw-bold text-primary mb-0"><?= formatPrice($product['price']) ?></p>
                            </div>
                            <div class="card-footer bg-transparent border-top-0">
                                <?php if ($product['stock'] > 0): ?>
                                    <a href="add_to_cart?id=<?= $product['id'] ?>"
                                        class="btn btn-outline-primary w-100">Add to Cart</a>
                                <?php else: ?>
                                    <button class="btn btn-outline-danger w-100" disabled>Out of Stock</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>


    <!-- All Products Section -->
    <section class="all-products">
        <h2 class="text-center section-title">Our Products</h2>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
            <?php if (empty($products)): ?>
                <div class="col-12">
                    <p class="text-center text-muted">No products found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="col">
                        <div class="card h-100 product-card">
                            <a href="product?id=<?= $product['id'] ?>">
                                <img src="admin/assets/uploads/<?= esc_html($product['image']) ?>"
                                    class="card-img-top product-card-img-top" alt="<?= esc_html($product['name']) ?>">
                            </a>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title h6"><a href="product?id=<?= $product['id'] ?>"
                                        class="text-dark text-decoration-none"><?= esc_html($product['name']) ?></a>
                                </h5>
                                <p class="card-text fw-bold text-primary mb-0"><?= formatPrice($product['price']) ?></p>
                            </div>
                            <div class="card-footer bg-transparent border-top-0">
                                <?php if ($product['stock'] > 0): ?>
                                    <a href="add_to_cart?id=<?= $product['id'] ?>"
                                        class="btn btn-outline-primary w-100">Add to Cart</a>
                                <?php else: ?>
                                    <button class="btn btn-outline-danger w-100" disabled>Out of Stock</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a class="btn btn-primary btn-lg  mt-4 " href="/all-products">See More</a>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
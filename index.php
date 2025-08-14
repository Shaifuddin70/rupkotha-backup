<?php
// This is your main storefront page, e.g., index

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

<!-- Hero Section with custom Slider -->
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
                            <div class="carousel-caption">
                                <h1><?= esc_html($item['title']) ?></h1>
                                <p class="lead"><?= esc_html($item['subtitle']) ?></p>
                                <div class="d-flex gap-3">
                                    <a class="btn btn-custom-primary" href="product?id=<?= $item['product_id'] ?>">
                                        Shop Now
                                    </a>
                                    <a class="btn btn-custom-outline" href="#new-arrivals">
                                        Explore
                                    </a>
                                </div>
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

<div class="main-content">
    <div class="container-custom">
        <!-- New Arrivals Section -->
        <section id="new-arrivals" class="section-custom">
            <h2 class="section-title text-center">New Arrivals</h2>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                <?php foreach ($new_arrivals as $product): ?>
                    <div class="col">
                        <div class="custom-product-card">
                            <div class="product-image-container">
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
                                <?php if ($product['stock'] > 0): ?>
                                    <a href="add_to_cart.php?id=<?= $product['id'] ?>" class="add-to-cart-btn">
                                        <i class="bi bi-cart-plus"></i>Add to Cart
                                    </a>
                                <?php else: ?>
                                    <button class="btn out-of-stock-custom" disabled>Out of Stock</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Top Selling Products Section -->
        <?php if (!empty($top_selling_products)): ?>
            <section class="section-custom">
                <h2 class="section-title text-center">Top Selling Products</h2>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                    <?php foreach ($top_selling_products as $product): ?>
                        <div class="col">
                            <div class="custom-product-card">
                                <div class="product-image-container">
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
                                    <?php if ($product['stock'] > 0): ?>
                                        <a href="add_to_cart.php?id=<?= $product['id'] ?>" class="add-to-cart-btn">
                                            <i class="bi bi-cart-plus"></i>Add to Cart
                                        </a>
                                    <?php else: ?>
                                        <button class="btn out-of-stock-custom" disabled>Out of Stock</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- All Products Section -->
        <section class="section-custom">
            <h2 class="section-title text-center">Our Products</h2>
            <?php if (empty($products)): ?>
                <div class="text-center">
                    <p class="text-muted fs-5">No products found.</p>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                    <?php foreach ($products as $product): ?>
                        <div class="col">
                            <div class="custom-product-card">
                                <div class="product-image-container">
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
                                    <?php if ($product['stock'] > 0): ?>
                                        <a href="add_to_cart.php?id=<?= $product['id'] ?>" class="add-to-cart-btn">
                                            <i class="bi bi-cart-plus"></i>Add to Cart
                                        </a>
                                    <?php else: ?>
                                        <button class="btn out-of-stock-custom" disabled>Out of Stock</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <a class="btn see-more-btn" href="/all-products">See More Products</a>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
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
$per_page = 4; // 4 products per page
$offset = ($page - 1) * $per_page;

// Get total number of products for pagination calculation
$total_products = $pdo->query("SELECT COUNT(id) FROM products WHERE is_active = 1")->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// 4. Fetch New Arrivals (e.g., the 4 most recent products)
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

<!-- Hero Section with Carousel and Side Cards -->
<section class="hero-section-horizontal">
    <div class="container">
        <div class="row align-items-center min-vh-70">
            <!-- Carousel Section (Left Side) -->
            <div class="col-lg-8">
                <?php if (!empty($hero_products)): ?>
                    <div id="heroCarousel" class="carousel slide hero-main-carousel" data-bs-ride="carousel" data-bs-interval="5000">
                        <div class="carousel-indicators hero-main-indicators">
                            <?php foreach ($hero_products as $index => $item): ?>
                                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $index ?>"
                                    class="<?= $index === 0 ? 'active' : '' ?>" aria-current="true"
                                    aria-label="Slide <?= $index + 1 ?>"></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="carousel-inner">
                            <?php foreach ($hero_products as $index => $item): ?>
                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                    <div class="hero-main-slide" style="background-image: url('admin/assets/uploads/<?= esc_html($item['image']) ?>');">
                                        <div class="hero-slide-overlay"></div>
                                        <div class="hero-slide-content">
                                            <h2 class="hero-slide-title"><?= esc_html($item['title']) ?></h2>
                                            <p class="hero-slide-subtitle"><?= esc_html($item['subtitle']) ?></p>
                                            <a href="product.php?id=<?= $item['product_id'] ?>" class="btn btn-hero-slide">
                                                Shop Now <i class="bi bi-arrow-right ms-2"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev hero-main-control" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                            <i class="bi bi-chevron-left"></i>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next hero-main-control" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                            <i class="bi bi-chevron-right"></i>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Cards Section (Right Side) -->
            <div class="col-lg-4 my-4">
                <div class="hero-cards-container">
                    <!-- New Products Card -->
                    <?php if (!empty($new_arrivals)): ?>
                        <?php $featured_new = $new_arrivals[0]; ?>
                        <a href="product.php?id=<?= $featured_new['id'] ?>" class="hero-side-card new-arrival-card card-link">
                            <div class="card-bg-image" style="background-image: url('admin/assets/uploads/<?= esc_html($featured_new['image']) ?>');">
                                <div class="card-bg-overlay"></div>
                                <div class="card-content-overlay">
                                    <div class="card-header-bg">
                                        <h3 class="card-title-bg">
                                            <i class="bi bi-star-fill me-2"></i>New Arrival
                                        </h3>
                                        <div class="product-badge-bg badge-new-bg">New</div>
                                    </div>
                                    <div class="card-body-bg">
                                        <h4 class="product-name-bg"><?= esc_html($featured_new['name']) ?></h4>
                                        <div class="product-price-bg"><?= formatPrice($featured_new['price']) ?></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endif; ?>

                    <!-- Top Selling Products Card -->
                    <?php if (!empty($top_selling_products)): ?>
                        <?php $featured_top = $top_selling_products[0]; ?>
                        <a href="product.php?id=<?= $featured_top['id'] ?>" class="hero-side-card top-selling-card card-link">
                            <div class="card-bg-image" style="background-image: url('admin/assets/uploads/<?= esc_html($featured_top['image']) ?>');">
                                <div class="card-bg-overlay"></div>
                                <div class="card-content-overlay">
                                    <div class="card-header-bg">
                                        <h3 class="card-title-bg">
                                            <i class="bi bi-trophy-fill me-2"></i>Top Selling
                                        </h3>
                                        <div class="product-badge-bg badge-top-bg">Best Seller</div>
                                    </div>
                                    <div class="card-body-bg">
                                        <h4 class="product-name-bg"><?= esc_html($featured_top['name']) ?></h4>
                                        <div class="product-price-bg"><?= formatPrice($featured_top['price']) ?></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Feature Icons Section -->
<section class="features-section-hero">
    <div class="container">
        <div class="row justify-content-center">


            <div class=" col-md-4 col-6 mb-3 mb-lg-0">
                <div class="feature-item-hero">
                    <div class="feature-icon-hero">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div class="feature-content-hero">
                        <h4>Fastest Home Delivery</h4>
                        <p>Quick shipping nationwide</p>
                    </div>
                </div>
            </div>


            <div class=" col-md-4 col-6 mb-3 mb-lg-0">
                <div class="feature-item-hero">
                    <div class="feature-icon-hero">
                        <i class="bi bi-tag"></i>
                    </div>
                    <div class="feature-content-hero">
                        <h4>Best Price Deals</h4>
                        <p>Competitive pricing</p>
                    </div>
                </div>
            </div>  

            <div class=" col-md-4 col-6 ">
                <div class="feature-item-hero">
                    <div class="feature-icon-hero">
                        <i class="bi bi-headset"></i>
                    </div>
                    <div class="feature-content-hero">
                        <h4>After Sell Service</h4>
                        <p>24/7 customer support</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

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
                                <?php if ($product['stock'] > 0): ?>
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
                                    <?php if ($product['stock'] > 0): ?>
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
                                    <?php if ($product['stock'] > 0): ?>
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
                </div>
                <a class="btn see-more-btn" href="/all-products">See More Products</a>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
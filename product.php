<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get product ID from URL and validate it.
// If the ID is missing or invalid, the redirect() function will now work correctly.
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$product_id) {
    redirect('all-products.php');
}

// --- 2. DATA FETCHING ---

// Fetch main product details. If not found, redirect.
$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name
     FROM products p
     JOIN categories c ON p.category_id = c.id
     WHERE p.id = ? AND p.is_active = 1 AND p.deleted_at IS NULL"
);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    redirect('all-products.php'); // This also works correctly now.
}

// Fetch additional product images
$image_stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY id");
$image_stmt->execute([$product_id]);
$additional_images = $image_stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine main and additional images
$all_images = array_merge([['image_path' => $product['image']]], $additional_images);

// Fetch related products
$related_stmt = $pdo->prepare(
    "SELECT * FROM products
     WHERE category_id = ? AND id != ? AND is_active = 1 AND deleted_at IS NULL
     LIMIT 3" // Limiting to 3 to fit nicely in the sidebar
);
$related_stmt->execute([$product['category_id'], $product_id]);
$related_products = $related_stmt->fetchAll(PDO::FETCH_ASSOC);

?>



<div class="product-page-custom">
    <div class="container-custom">
        <!-- custom Breadcrumb -->
        <!-- <div class="breadcrumb-custom">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index">Home</a></li>
                    <li class="breadcrumb-item">
                        <a href="category.php?id=<?= $product['category_id'] ?>">
                            <?= esc_html($product['category_name']) ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= esc_html($product['name']) ?>
                    </li>
                </ol>
            </nav>
        </div> -->

        <div class="row">
            <!-- Product Gallery -->
            <div class="col-lg-7 mb-5 mb-lg-0">
                <div class="product-gallery-custom">
                    <div class="main-image-custom">
                        <img id="main-product-image"
                            src="admin/assets/uploads/<?= esc_html($product['image']) ?>"
                            alt="<?= esc_html($product['name']) ?>">
                        <div class="zoom-overlay">
                            <i class="bi bi-zoom-in"></i> Click to zoom
                        </div>
                    </div>

                    <?php if (count($all_images) > 1): ?>
                        <div class="thumbnail-gallery-custom">
                            <?php foreach ($all_images as $index => $img): ?>
                                <div class="thumb-custom <?= $index === 0 ? 'active' : '' ?>">
                                    <img src="admin/assets/uploads/<?= esc_html($img['image_path']) ?>"
                                        alt="<?= esc_html($product['name']) ?> thumbnail">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Details -->
            <div class="col-lg-5">
                <div class="product-details-custom">
                    <h1 class="product-title-custom"><?= esc_html($product['name']) ?></h1>

                    <div class="price-stock-container">
                        <div class="price-custom"><?= formatPrice($product['price']) ?></div>
                        <?php if ($product['stock'] > 0): ?>
                            <div class="stock-badge-custom available">
                                <i class="bi bi-check-circle-fill"></i>
                                In Stock (<?= $product['stock'] ?> available)
                            </div>
                        <?php else: ?>
                            <div class="stock-badge-custom unavailable">
                                <i class="bi bi-x-circle-fill"></i>
                                Out of Stock
                            </div>
                        <?php endif; ?>
                    </div>

                    <p class="product-description-custom">
                        <?= esc_html($product['short_description'] ?? 'Discover this amazing product with exceptional quality and custom design.') ?>
                    </p>

                    <form action="add_to_cart.php" method="POST" class="add-to-cart-form-custom">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                        <div class="quantity-container-custom">
                            <span class="quantity-label-custom">Quantity:</span>
                            <div class="quantity-selector-custom">
                                <button type="button" class="quantity-btn-custom" data-action="decrease">−</button>
                                <input type="number" name="quantity" class="quantity-input-custom"
                                    value="1" min="1" max="<?= $product['stock'] ?>"
                                    <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                                <button type="button" class="quantity-btn-custom" data-action="increase">+</button>
                            </div>
                        </div>

                        <button type="submit" class="btn add-to-cart-custom"
                            <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                            <i class="bi bi-cart-plus-fill me-2"></i>
                            <?= $product['stock'] <= 0 ? 'Out of Stock' : 'Add to Cart' ?>
                        </button>
                    </form>

                    <div class="product-meta-custom">
                        <div class="meta-item-custom">
                            <span class="meta-label-custom">Category:</span>
                            <span class="meta-value-custom">
                                <a href="category.php?id=<?= $product['category_id'] ?>">
                                    <?= esc_html($product['category_name']) ?>
                                </a>
                            </span>
                        </div>
                        <div class="meta-item-custom">
                            <span class="meta-label-custom">SKU:</span>
                            <span class="meta-value-custom">#<?= $product['id'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Product Description -->
            <div class="col-lg-8">
                <div class="description-section-custom">
                    <h3 class="section-title-custom">Product Description</h3>
                    <div class="description-content-custom">
                        <?= nl2br(esc_html($product['description'])) ?>
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            <div class="col-lg-4">
                <?php if (!empty($related_products)): ?>
                    <div class="related-products-custom">
                        <h3 class="section-title-custom">You Might Also Like</h3>

                        <?php foreach ($related_products as $related): ?>
                            <div class="related-product-custom">
                                <div class="related-product-img-custom">
                                    <a href="product.php?id=<?= $related['id'] ?>">
                                        <img src="admin/assets/uploads/<?= esc_html($related['image']) ?>"
                                            alt="<?= esc_html($related['name']) ?>">
                                    </a>
                                </div>
                                <div class="related-product-details-custom">
                                    <h5 class="related-product-title-custom">
                                        <a href="product.php?id=<?= $related['id'] ?>">
                                            <?= esc_html($related['name']) ?>
                                        </a>
                                    </h5>
                                    <div class="related-product-price-custom">
                                        <?= formatPrice($related['price']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Image Gallery Functionality
        const mainImage = document.getElementById('main-product-image');
        const thumbnails = document.querySelectorAll('.thumb-custom');

        if (thumbnails.length > 0) {
            thumbnails[0].classList.add('active');
        }

        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                const img = this.querySelector('img');
                mainImage.src = img.src;

                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                // Add loading effect
                mainImage.style.opacity = '0.5';
                setTimeout(() => {
                    mainImage.style.opacity = '1';
                }, 200);
            });
        });

        // Quantity Controls
        document.querySelectorAll('.quantity-btn-custom').forEach(button => {
            button.addEventListener('click', function() {
                const action = this.dataset.action;
                const input = this.parentElement.querySelector('.quantity-input-custom');
                let value = parseInt(input.value);
                const max = parseInt(input.max);

                if (action === 'increase' && (isNaN(max) || value < max)) {
                    value++;
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => this.style.transform = 'scale(1)', 100);
                } else if (action === 'decrease' && value > 1) {
                    value--;
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => this.style.transform = 'scale(1)', 100);
                }

                input.value = value;

                // Add a subtle animation to the input
                input.style.transform = 'scale(1.05)';
                input.style.color = '#667eea';
                setTimeout(() => {
                    input.style.transform = 'scale(1)';
                    input.style.color = '#2d3748';
                }, 200);
            });
        });

        // Enhanced Add to Cart Button
        const addToCartBtn = document.querySelector('.add-to-cart-custom');
        if (addToCartBtn && !addToCartBtn.disabled) {
            addToCartBtn.addEventListener('click', function(e) {
                // Add loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Adding...';
                this.style.background = 'var(--accent-gradient)';

                // Reset after form submission (you might want to handle this differently)
                setTimeout(() => {
                    this.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Added!';
                    this.style.background = 'var(--success-gradient)';

                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.background = 'var(--primary-gradient)';
                    }, 2000);
                }, 1000);
            });
        }

        // Smooth scrolling for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // // Intersection Observer for animations
        // const observerOptions = {
        //     threshold: 0.1,
        //     rootMargin: '0px 0px -50px 0px'
        // };

        // const observer = new IntersectionObserver((entries) => {
        //     entries.forEach(entry => {
        //         if (entry.isIntersecting) {
        //             entry.target.style.opacity = '1';
        //             entry.target.style.transform = 'translateY(0)';
        //         }
        //     });
        // }, observerOptions);

        // // Observe elements for animation
        // document.querySelectorAll('.product-gallery-custom, .product-details-custom, .description-section-custom, .related-products-custom').forEach(el => {
        //     el.style.opacity = '0';
        //     el.style.transform = 'translateY(30px)';
        //     el.style.transition = 'all 0.6s ease';
        //     observer.observe(el);
        // });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
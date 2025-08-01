<?php
require_once 'includes/header.php';
require_once 'includes/functions.php';

// Get product ID from URL and validate it
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$product_id) {
    redirect('all-products.php');
}

// --- FETCH MAIN PRODUCT DETAILS ---
// This part fetches the core details of the product being viewed.
$stmt = $pdo->prepare(
    "SELECT p.*, c.name AS category_name
     FROM products p
     JOIN categories c ON p.category_id = c.id
     WHERE p.id = ? AND p.is_active = 1 AND p.deleted_at IS NULL"
);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

// If the product doesn't exist or isn't active, redirect the user.
if (!$product) {
    redirect('all-products.php');
}

// --- FETCH ADDITIONAL PRODUCT IMAGES ---
// This gets all other images associated with this product.
$image_stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY id");
$image_stmt->execute([$product_id]);
$additional_images = $image_stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine the main product image with the additional images to create a full gallery list.
$all_images = array_merge([['image_path' => $product['image']]], $additional_images);

// --- FETCH RELATED PRODUCTS ---
// This finds other products from the same category to suggest to the user.
$related_stmt = $pdo->prepare(
    "SELECT * FROM products
     WHERE category_id = ? AND id != ? AND is_active = 1 AND deleted_at IS NULL
     LIMIT 3"
);
$related_stmt->execute([$product['category_id'], $product_id]);
$related_products = $related_stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<div class="container product-page-container">
    <div class="row">
        <!-- Image Gallery Column -->
        <div class="col-lg-7">
            <div class="product-gallery-container">
                <!-- Main Image -->
                <div class="main-image-wrapper">
                    <img id="main-product-image" src="admin/assets/uploads/<?= esc_html($product['image']) ?>"
                        alt="<?= esc_html($product['name']) ?>" class="img-fluid">
                </div>
                <!-- Thumbnails -->
                <div class="thumbnail-list">
                    <?php if (count($all_images) > 1): ?>
                        <?php foreach ($all_images as $index => $img): ?>
                            <div class="thumb-image-wrapper <?= $index === 0 ? 'active' : '' ?>">
                                <img src="admin/assets/uploads/<?= esc_html($img['image_path']) ?>"
                                    alt="Thumbnail of <?= esc_html($product['name']) ?>" class="img-fluid thumb-image">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Product Details Column -->
        <div class="col-lg-5">
            <div class="product-details-section">
                <a href="category.php?id=<?= $product['category_id'] ?>" class="product-category-link"><?= esc_html($product['category_name']) ?></a>
                <h1 class="product-title"><?= esc_html($product['name']) ?></h1>

                <div class="price-stock-container">
                    <span class="price-display"><?= formatPrice($product['price']) ?></span>
                    <?php if ($product['stock'] > 0): ?>
                        <span class="badge bg-success-soft text-success stock-badge"><i class="bi bi-check-circle me-1"></i> In Stock</span>
                    <?php else: ?>
                        <span class="badge bg-danger-soft text-danger stock-badge"><i class="bi bi-x-circle me-1"></i> Out of Stock</span>
                    <?php endif; ?>
                </div>

                <p class="product-short-description"><?= esc_html($product['short_description'] ?? 'No short description available.') ?></p>

                <form action="add_to_cart.php" method="POST" class="add-to-cart-form">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="quantity-selector">
                            <button type="button" class="btn quantity-btn" data-action="decrease">-</button>
                            <input type="number" name="quantity" class="form-control quantity-input" value="1" min="1"
                                max="<?= $product['stock'] ?>" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                            <button type="button" class="btn quantity-btn" data-action="increase">+</button>
                        </div>
                        <button type="submit"
                            class="btn btn-primary btn-add-to-cart" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                            <i class="bi bi-cart-plus-fill me-2"></i> Add to Cart
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Description and Related Products Row -->
    <div class="row mt-5 pt-4 border-top">
        <!-- Left Column: Description -->
        <div class="col-lg-8">
            <div class="product-info-tabs">
                <ul class="nav nav-tabs" id="productTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="true">Description</button>
                    </li>
                </ul>
                <div class="tab-content" id="productTabContent">
                    <div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
                        <?= nl2br(esc_html($product['description'])) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Related Products -->
        <div class="col-lg-4">
            <?php if (!empty($related_products)): ?>
                <div class="related-products-sidebar mt-4 mt-lg-0">
                    <h3 class="section-title">You Might Also Like</h3>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($related_products as $related): ?>
                            <div class="related-product-card">
                                <a href="product.php?id=<?= $related['id'] ?>" class="related-product-img-link">
                                    <img src="admin/assets/uploads/<?= esc_html($related['image']) ?>" class="related-product-img"
                                        alt="<?= esc_html($related['name']) ?>">
                                </a>
                                <div class="card-body">
                                    <h5 class="card-title mb-1">
                                        <a href="product.php?id=<?= $related['id'] ?>"><?= esc_html($related['name']) ?></a>
                                    </h5>
                                    <p class="card-text price mb-0"><?= formatPrice($related['price']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mainImage = document.getElementById('main-product-image');
        const thumbWrappers = document.querySelectorAll('.thumb-image-wrapper');

        thumbWrappers.forEach(wrapper => {
            wrapper.addEventListener('click', function() {
                // Get the new image source from the clicked thumbnail
                const newSrc = this.querySelector('.thumb-image').src;
                mainImage.src = newSrc;

                // Update active state for all thumbnail wrappers
                thumbWrappers.forEach(w => w.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Quantity selector logic
        document.querySelectorAll('.quantity-btn').forEach(button => {
            button.addEventListener('click', function() {
                const action = this.dataset.action;
                const input = this.closest('.quantity-selector').querySelector('.quantity-input');
                let value = parseInt(input.value);
                const max = parseInt(input.max);

                if (action === 'increase' && (isNaN(max) || value < max)) {
                    value++;
                } else if (action === 'decrease' && value > 1) {
                    value--;
                }
                input.value = value;
            });
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
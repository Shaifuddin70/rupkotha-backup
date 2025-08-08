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

<link rel="stylesheet" href="assets/css/product-style.css">

<div class="product-page-container my-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-7">
                <div class="product-gallery-new">
                    <div class="main-image-wrapper mb-3">
                        <img id="main-product-image" src="admin/assets/uploads/<?= esc_html($product['image']) ?>"
                            alt="<?= esc_html($product['name']) ?>" class="img-fluid">
                    </div>
                    <?php if (count($all_images) > 1): ?>
                        <div class="thumbnail-scroller">
                            <?php foreach ($all_images as $index => $img): ?>
                                <img src="admin/assets/uploads/<?= esc_html($img['image_path']) ?>"
                                    alt="Thumbnail of <?= esc_html($product['name']) ?>"
                                    class="thumb-image-new <?= $index === 0 ? 'active' : '' ?>">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="product-details-new">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="category.php?id=<?= $product['category_id'] ?>"><?= esc_html($product['category_name']) ?></a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?= esc_html($product['name']) ?></li>
                        </ol>
                    </nav>
                    <h1 class="product-title-new"><?= esc_html($product['name']) ?></h1>
                    <div class="price-stock-wrapper mb-4">
                        <span class="price-display-new"><?= formatPrice($product['price']) ?></span>
                        <?php if ($product['stock'] > 0): ?>
                            <span class="stock-badge available"><i class="bi bi-check-circle"></i> In Stock</span>
                        <?php else: ?>
                            <span class="stock-badge unavailable"><i class="bi bi-x-circle"></i> Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    <p class="product-short-description"><?= esc_html($product['short_description'] ?? 'No short description available.') ?></p>
                    <form action="add_to_cart.php" method="POST" class="add-to-cart-form mt-4">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <div class="row g-2 align-items-center">
                            <div class="col-auto">
                                <div class="quantity-selector-new">
                                    <button type="button" class="btn quantity-btn-new" data-action="decrease">-</button>
                                    <input type="number" name="quantity" class="form-control quantity-input-new" value="1" min="1" max="<?= $product['stock'] ?>" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                                    <button type="button" class="btn quantity-btn-new" data-action="increase">+</button>
                                </div>
                            </div>
                            <div class="col">
                                <button type="submit" class="btn btn-primary btn-lg w-100 cart-btn-new" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                                    <i class="bi bi-cart-plus"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </form>
                    <div class="product-meta-new mt-4">
                        <strong>Category:</strong> <a href="category.php?id=<?= $product['category_id'] ?>"><?= esc_html($product['category_name']) ?></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5 ">
            <div class="col-lg-8">
                <div class="product-description-section">
                    <h3 class="section-title">Product Description</h3>
                    <div class="description-content">
                        <?= nl2br(esc_html($product['description'])) ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <?php if (!empty($related_products)): ?>
                    <div class="related-products-sidebar">
                        <h3 class="section-title">You Might Also Like</h3>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($related_products as $related): ?>
                                <div class="related-product-card">
                                    <a href="product.php?id=<?= $related['id'] ?>" class="related-product-img-link">
                                        <img src="admin/assets/uploads/<?= esc_html($related['image']) ?>" class="related-product-img"
                                            alt="<?= esc_html($related['name']) ?>">
                                    </a>
                                    <div class="related-product-details">
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
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mainImage = document.getElementById('main-product-image');
        const thumbnails = document.querySelectorAll('.thumb-image-new');

        if (thumbnails.length > 0) {
            thumbnails[0].classList.add('active');
        }

        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                mainImage.src = this.src;
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });

        document.querySelectorAll('.quantity-btn-new').forEach(button => {
            button.addEventListener('click', function() {
                const action = this.dataset.action;
                const input = this.parentElement.querySelector('.quantity-input-new');
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
<?php
// This is your shopping cart page, e.g., cart.php

// STEP 1: Start session and include necessary logic files FIRST.
// Do not include header.php here yet.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';

// STEP 2: Handle all form processing and potential redirects BEFORE any HTML output.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle updating quantities
    if (isset($_POST['update_cart'])) {
        if (!empty($_POST['quantities']) && is_array($_POST['quantities'])) {
            foreach ($_POST['quantities'] as $product_id => $quantity) {
                $product_id = (int)$product_id;

                // Only process if the cart item exists
                if (isset($_SESSION['cart'][$product_id])) {
                    $original_quantity = $_SESSION['cart'][$product_id];
                    $raw_input = $_POST['quantities'][$product_id];

                    // More robust quantity validation
                    if (empty($raw_input) || !is_numeric($raw_input)) {
                        // Keep existing quantity if input is empty or non-numeric
                        continue;
                    }

                    $quantity = (int)$quantity;

                    if ($quantity > 0) {
                        // Valid positive quantity - update with stock check
                        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
                        $stmt->execute([$product_id]);
                        $stock = $stmt->fetchColumn();
                        $_SESSION['cart'][$product_id] = min($quantity, $stock);
                    } elseif ($quantity === 0 && $raw_input === '0') {
                        // Only remove if user explicitly set to 0
                        unset($_SESSION['cart'][$product_id]);
                    } else {
                        // Keep existing quantity for any other invalid input
                        $_SESSION['cart'][$product_id] = $original_quantity;
                    }
                }
            }
        }
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Cart updated successfully.'];
    }

    // Handle removing a single item
    if (isset($_POST['remove_item'])) {
        $product_id_to_remove = (int)$_POST['product_id'];
        unset($_SESSION['cart'][$product_id_to_remove]);
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Item removed from cart.'];
    }

    // Redirect back to the cart page to prevent form resubmission.
    // This now works because no HTML has been sent yet.
    redirect('cart.php');
}


// STEP 3: Now that all logic is done, include the header to start page output.
include 'includes/header.php';


// --- Data Fetching for Cart Display ---
$cart_items = $_SESSION['cart'] ?? [];
$products_in_cart = [];
$subtotal = 0;

if (!empty($cart_items)) {
    $product_ids = array_keys($cart_items);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products_in_cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate subtotal
    foreach ($products_in_cart as $product) {
        $quantity = $cart_items[$product['id']];
        $subtotal += $product['price'] * $quantity;
    }
}

// Define shipping cost
$shipping_cost = 50.00; // Example fixed shipping cost
$grand_total = $subtotal + $shipping_cost;

?>
<div class="products-hero">
    <div class="container text-center">
        <h1 class="fade-in">Shopping Cart</h1>
    </div>
</div>

<main class="container my-5">
    <div class="row">
        <?php if (empty($products_in_cart)): ?>
            <div class="col-12 text-center">
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-cart-x"></i>
                    </div>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added anything to your cart yet. Start exploring our products!</p>
                    <a href="index" class="add-to-cart-btn d-inline-flex">
                        <i class="bi bi-arrow-left me-2"></i>Continue Shopping
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Cart Items Column -->
            <div class="col-lg-8">
                <form action="cart.php" method="post">
                    <div class="glass-panel">
                        <h5 class="mb-4 fw-bold">Cart Items (<?= count($products_in_cart) ?>)</h5>

                        <!-- Desktop Table View -->
                        <div class="table-responsive d-none d-md-block cart-table-desktop">
                            <table class="table align-middle mb-0">
                                <tbody>
                                    <?php foreach ($products_in_cart as $product): ?>
                                        <tr class="fade-in cart-item-row">
                                            <td style="width: 120px;">
                                                <a href="product.php?id=<?= $product['id'] ?>">
                                                    <img src="admin/assets/uploads/<?= esc_html($product['image']) ?>" alt="<?= esc_html($product['name']) ?>" class="cart-item-image">
                                                </a>
                                            </td>
                                            <td>
                                                <a href="product.php?id=<?= $product['id'] ?>" class="text-dark text-decoration-none fw-bold product-name"><?= esc_html($product['name']) ?></a>
                                                <p class="text-muted small mb-0 product-price-custom">Price: <?= formatPrice($product['price']) ?></p>
                                            </td>
                                            <td style="width: 150px;">
                                                <div class="quantity-controls">
                                                    <input type="number" name="quantities[<?= $product['id'] ?>]" class="quantity-input" value="<?= $cart_items[$product['id']] ?>" min="1" max="<?= $product['stock'] ?>">
                                                </div>
                                            </td>
                                            <td class="text-end fw-bold" style="width: 120px;">
                                                <?= formatPrice($product['price'] * $cart_items[$product['id']]) ?>
                                            </td>
                                            <td class="text-end" style="width: 50px;">
                                                <!-- Use a separate form for each remove button to avoid conflicts -->
                                                <form action="cart.php" method="post" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                    <button type="submit" name="remove_item" class="remove-btn" title="Remove item"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Card View -->
                        <div class="d-md-none cart-mobile-container">
                            <?php foreach ($products_in_cart as $product): ?>
                                <div class="cart-item-mobile fade-in">
                                    <div class="cart-item-mobile-header">
                                        <a href="product.php?id=<?= $product['id'] ?>">
                                            <img src="admin/assets/uploads/<?= esc_html($product['image']) ?>" alt="<?= esc_html($product['name']) ?>" class="cart-item-mobile-image">
                                        </a>
                                        <div class="cart-item-mobile-info">
                                            <a href="product.php?id=<?= $product['id'] ?>" class="text-decoration-none">
                                                <h6 class="cart-item-mobile-name"><?= esc_html($product['name']) ?></h6>
                                            </a>
                                            <div class="cart-item-mobile-price">Price: <?= formatPrice($product['price']) ?></div>
                                        </div>
                                    </div>
                                    <div class="cart-item-mobile-controls">
                                        <div class="cart-mobile-quantity">
                                            <button type="button" onclick="decreaseQuantity(<?= $product['id'] ?>)">-</button>
                                            <input type="number" id="qty-display-<?= $product['id'] ?>" value="<?= $cart_items[$product['id']] ?>" min="1" max="<?= $product['stock'] ?>" onchange="updateMobileQuantity(<?= $product['id'] ?>, this.value, <?= $product['stock'] ?>)">
                                            <button type="button" onclick="increaseQuantity(<?= $product['id'] ?>, <?= $product['stock'] ?>)">+</button>
                                        </div>
                                        <!-- Hidden input that syncs with the mobile display -->
                                        <input type="hidden" name="quantities[<?= $product['id'] ?>]" id="qty-hidden-<?= $product['id'] ?>" value="<?= $cart_items[$product['id']] ?>">
                                        <div class="cart-mobile-total">
                                            Total: <span id="total-<?= $product['id'] ?>"><?= formatPrice($product['price'] * $cart_items[$product['id']]) ?></span>
                                        </div>
                                        <form action="cart.php" method="post" class="d-inline">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <button type="submit" name="remove_item" class="cart-mobile-remove" title="Remove item">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="cart-actions-mobile d-md-flex justify-content-between align-items-center mt-4 pt-3">
                            <a href="all-products.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Continue Shopping</a>
                            <button type="submit" name="update_cart" class="btn btn-primary"><i class="bi bi-arrow-repeat me-2"></i>Update Cart</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Summary Column -->
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h5 class="mb-4 fw-bold text-white">Cart Summary</h5>
                    <div class="summary-item">
                        <span>Subtotal</span>
                        <span><?= formatPrice($subtotal) ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Shipping</span>
                        <span><?= formatPrice($shipping_cost) ?></span>
                    </div>
                    <div class="summary-item">
                        <div>
                            <strong>Total amount</strong>
                            <div class="small opacity-75">(including VAT)</div>
                        </div>
                        <span class="fw-bold"><?= formatPrice($grand_total) ?></span>
                    </div>
                    <div class="mt-4">
                        <?php if (isLoggedIn()): ?>
                            <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                        <?php else: ?>
                            <a href="login.php?redirect=checkout.php" class="checkout-btn">Login to Checkout</a>
                            <p class="text-center small mt-2 opacity-75">You must be logged in to place an order.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    // Store product prices for calculation
    const productPrices = {
        <?php foreach ($products_in_cart as $product): ?>
            <?= $product['id'] ?>: <?= $product['price'] ?>,
        <?php endforeach; ?>
    };

    // Mobile quantity control functions
    function increaseQuantity(productId, maxStock) {
        const displayInput = document.getElementById('qty-display-' + productId);
        const currentValue = parseInt(displayInput.value);
        if (currentValue < maxStock) {
            const newValue = currentValue + 1;
            displayInput.value = newValue;
            updateMobileQuantity(productId, newValue, maxStock);
        }
    }

    function decreaseQuantity(productId) {
        const displayInput = document.getElementById('qty-display-' + productId);
        const currentValue = parseInt(displayInput.value);
        if (currentValue > 1) {
            const newValue = currentValue - 1;
            displayInput.value = newValue;
            updateMobileQuantity(productId, newValue);
        }
    }

    function updateMobileQuantity(productId, quantity, maxStock) {
        const quantityInt = parseInt(quantity);

        // Validate quantity
        if (isNaN(quantityInt) || quantityInt < 1) {
            // Reset to 1 if invalid
            document.getElementById('qty-display-' + productId).value = 1;
            document.getElementById('qty-hidden-' + productId).value = 1;
            updateMobileTotal(productId, 1);
            return;
        }

        // Check against max stock
        const finalQuantity = maxStock ? Math.min(quantityInt, maxStock) : quantityInt;

        // Update both display and hidden inputs
        document.getElementById('qty-display-' + productId).value = finalQuantity;
        document.getElementById('qty-hidden-' + productId).value = finalQuantity;

        // Update total display
        updateMobileTotal(productId, finalQuantity);

        // Auto-submit after a delay
        clearTimeout(window.updateTimeout);
        window.updateTimeout = setTimeout(() => {
            const form = document.querySelector('form[method="post"]');
            if (form) {
                const updateBtn = form.querySelector('button[name="update_cart"]');
                if (updateBtn) {
                    updateBtn.click();
                }
            }
        }, 1500); // 1.5 second delay
    }

    function updateMobileTotal(productId, quantity) {
        const price = productPrices[productId];
        if (price && quantity) {
            const total = price * quantity;
            const totalElement = document.getElementById('total-' + productId);
            if (totalElement) {
                // Format price similar to PHP formatPrice function
                totalElement.textContent = '৳' + total.toLocaleString('en-BD', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        }
    }

    // Add auto-submit functionality for desktop quantity inputs
    document.addEventListener('DOMContentLoaded', function() {
        // Only add auto-submit to desktop quantity inputs (not mobile hidden ones)
        const desktopQuantityInputs = document.querySelectorAll('.quantity-input[name^="quantities["]');

        desktopQuantityInputs.forEach(input => {
            input.addEventListener('change', function() {
                // Validate the input value
                const value = parseInt(this.value);
                const min = parseInt(this.min) || 1;
                const max = parseInt(this.max) || 999;

                if (isNaN(value) || value < min) {
                    this.value = min;
                } else if (value > max) {
                    this.value = max;
                }

                clearTimeout(window.updateTimeout);
                window.updateTimeout = setTimeout(() => {
                    // Auto-submit the form after a short delay
                    const form = this.closest('form');
                    if (form) {
                        const updateBtn = form.querySelector('button[name="update_cart"]');
                        if (updateBtn) {
                            updateBtn.click();
                        }
                    }
                }, 1500); // 1.5 second delay
            });
        });

        // Sync mobile quantity displays with their hidden inputs on page load
        <?php foreach ($products_in_cart as $product): ?>
            const hiddenInput<?= $product['id'] ?> = document.getElementById('qty-hidden-<?= $product['id'] ?>');
            const displayInput<?= $product['id'] ?> = document.getElementById('qty-display-<?= $product['id'] ?>');
            if (hiddenInput<?= $product['id'] ?> && displayInput<?= $product['id'] ?>) {
                displayInput<?= $product['id'] ?>.value = hiddenInput<?= $product['id'] ?>.value;
            }
        <?php endforeach; ?>
    });
</script>

<?php include 'includes/footer.php'; ?>
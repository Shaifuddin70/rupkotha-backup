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
                $quantity = (int)$quantity;

                if ($quantity > 0 && isset($_SESSION['cart'][$product_id])) {
                    // You should uncomment and use this stock check for robustness
                    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
                    $stmt->execute([$product_id]);
                    $stock = $stmt->fetchColumn();
                    $_SESSION['cart'][$product_id] = min($quantity, $stock);
                } else {
                    // If quantity is 0 or less, remove the item
                    unset($_SESSION['cart'][$product_id]);
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
                    <div class="glass-panel p-4">
                        <h5 class="mb-4 fw-bold">Cart Items (<?= count($products_in_cart) ?>)</h5>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <tbody>
                                    <?php foreach ($products_in_cart as $product): ?>
                                        <tr class="fade-in">
                                            <td style="width: 120px;">
                                                <a href="product.php?id=<?= $product['id'] ?>">
                                                    <img src="admin/assets/uploads/<?= esc_html($product['image']) ?>" alt="<?= esc_html($product['name']) ?>" class="img-fluid rounded-3">
                                                </a>
                                            </td>
                                            <td>
                                                <a href="product.php?id=<?= $product['id'] ?>" class="text-dark text-decoration-none fw-bold product-name"><?= esc_html($product['name']) ?></a>
                                                <p class="text-muted small mb-0 product-price-custom">Price: <?= formatPrice($product['price']) ?></p>
                                            </td>
                                            <td style="width: 150px;">
                                                <div class="input-group">
                                                    <input type="number" name="quantities[<?= $product['id'] ?>]" class="form-control text-center" value="<?= $cart_items[$product['id']] ?>" min="1" max="<?= $product['stock'] ?>">
                                                </div>
                                            </td>
                                            <td class="text-end fw-bold" style="width: 120px;">
                                                <?= formatPrice($product['price'] * $cart_items[$product['id']]) ?>
                                            </td>
                                            <td class="text-end" style="width: 50px;">
                                                <!-- Use a separate form for each remove button to avoid conflicts -->
                                                <form action="cart.php" method="post" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                    <button type="submit" name="remove_item" class="btn btn-sm btn-danger rounded-circle" title="Remove item"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 ">
                            <a href="all-products.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Continue Shopping</a>
                            <button type="submit" name="update_cart" class="btn btn-primary"><i class="bi bi-arrow-repeat me-2"></i>Update Cart</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Summary Column -->
            <div class="col-lg-4">
                <div class="glass-panel p-4">
                    <h5 class="mb-4 fw-bold">Cart Summary</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 pb-0">
                            Subtotal
                            <span><?= formatPrice($subtotal) ?></span>
                        </li>

                        <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0 mb-3  pt-3 mt-3">
                            <div>
                                <strong>Total amount</strong>
                                <p class="mb-0 small text-muted">(including VAT)</p>
                            </div>
                            <span class="fw-bold fs-5"><?= formatPrice($grand_total) ?></span>
                        </li>
                    </ul>
                    <div class="d-grid mt-4">
                        <?php if (isLoggedIn()): ?>
                            <a href="checkout.php" class="btn btn-custom-primary btn-lg">Proceed to Checkout</a>
                        <?php else: ?>
                            <a href="login.php?redirect=checkout.php" class="btn btn-custom-primary btn-lg">Login to Checkout</a>
                            <p class="text-center small mt-2 text-muted">You must be logged in to place an order.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
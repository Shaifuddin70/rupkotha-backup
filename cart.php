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

<div class="page-header" style="background-color: #f8f9fa; padding: 2rem 0; margin-bottom: 3rem;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Shopping Cart</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold mt-2">Your Cart</h1>
    </div>
</div>

<main class="container my-5">
    <div class="row">
        <?php if (empty($products_in_cart)): ?>
            <div class="col-12 text-center">
                <div class="card p-5">
                    <div class="card-body">
                        <i class="bi bi-cart-x" style="font-size: 5rem; color: #6c757d;"></i>
                        <h3 class="card-title mt-4">Your cart is empty</h3>
                        <p class="card-text text-muted">Looks like you haven't added anything to your cart yet.</p>
                        <a href="index" class="btn btn-primary mt-3">Continue Shopping</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Cart Items Column -->
            <div class="col-lg-8">
                <form action="cart" method="post">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Cart Items (<?= count($products_in_cart) ?>)</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <tbody>
                                <?php foreach ($products_in_cart as $product): ?>
                                    <tr>
                                        <td style="width: 120px;">
                                            <a href="product?id=<?= $product['id'] ?>">
                                                <img src="admin/assets/uploads/<?= esc_html($product['image']) ?>" alt="<?= esc_html($product['name']) ?>" class="img-fluid rounded">
                                            </a>
                                        </td>
                                        <td>
                                            <a href="product?id=<?= $product['id'] ?>" class="text-dark text-decoration-none fw-bold"><?= esc_html($product['name']) ?></a>
                                            <p class="text-muted small mb-0">Price: <?= formatPrice($product['price']) ?></p>
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
                                            <form action="cart" method="post" class="d-inline">
                                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                <button type="submit" name="remove_item" class="btn btn-sm btn-outline-danger" title="Remove item"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
                            <a href="index" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Continue Shopping</a>
                            <button type="submit" name="update_cart" class="btn btn-primary"><i class="bi bi-arrow-repeat me-2"></i>Update Cart</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Summary Column -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Cart Summary</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 pb-0">
                                Subtotal
                                <span><?= formatPrice($subtotal) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                Shipping
                                <span><?= formatPrice($shipping_cost) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 mb-3">
                                <div>
                                    <strong>Total amount</strong>
                                    <p class="mb-0 small text-muted">(including VAT)</p>
                                </div>
                                <span class="fw-bold fs-5"><?= formatPrice($grand_total) ?></span>
                            </li>
                        </ul>
                        <div class="d-grid">
                            <?php if (isLoggedIn()): ?>
                                <a href="checkout" class="btn btn-primary btn-lg">Proceed to Checkout</a>
                            <?php else: ?>
                                <a href="login?redirect=checkout" class="btn btn-primary btn-lg">Login to Checkout</a>
                                <p class="text-center small mt-2 text-muted">You must be logged in to place an order.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

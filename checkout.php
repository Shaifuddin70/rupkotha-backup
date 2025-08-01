<?php
// This is the checkout page, e.g., checkout

// STEP 1: Start session and include necessary files FIRST.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';

// STEP 2: Authentication and Cart Checks.
if (!isLoggedIn()) {
    redirect('login?redirect=checkout');
}
if (empty($_SESSION['cart'])) {
    redirect('cart');
}

$user_id = $_SESSION['user_id'];
$errors = [];

// --- DATA FETCHING for both GET and POST ---

// Fetch store settings (shipping fees, payment numbers)
$settings_stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$settings = $settings_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$cart_items = $_SESSION['cart'] ?? [];
$products_in_cart = [];
$subtotal = 0;

if (!empty($cart_items)) {
    $product_ids = array_keys($cart_items);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products_from_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $product_map = [];
    foreach ($products_from_db as $p) {
        $product_map[$p['id']] = $p;
    }

    foreach ($cart_items as $product_id => $quantity) {
        if (isset($product_map[$product_id])) {
            $product = $product_map[$product_id];
            if ($quantity > $product['stock']) {
                $errors[] = "Not enough stock for " . esc_html($product['name']) . ". Only " . $product['stock'] . " available.";
            }
            $products_in_cart[] = $product;
            $subtotal += $product['price'] * $quantity;
        }
    }
}

// Set initial shipping cost based on default selection (Dhaka)
$shipping_fee = $settings['shipping_fee_dhaka'] ?? 60.00;
$grand_total = $subtotal + $shipping_fee;


// STEP 3: Handle form submission for placing the order.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    // Sanitize shipping details from form
    $shipping_name = trim($_POST['full_name'] ?? '');
    $shipping_phone = trim($_POST['phone'] ?? '');
    $shipping_address = trim($_POST['address'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? '');
    $shipping_location = trim($_POST['shipping_location'] ?? 'dhaka');

    // Recalculate shipping fee and total based on submitted form data for accuracy
    $final_shipping_fee = ($shipping_location === 'outside') ? ($settings['shipping_fee_outside'] ?? 120.00) : ($settings['shipping_fee_dhaka'] ?? 60.00);
    $final_grand_total = $subtotal + $final_shipping_fee;

    // Payment specific details
    $payment_sender_no = trim($_POST['payment_sender_no'] ?? '');
    $payment_trx_id = trim($_POST['payment_trx_id'] ?? '');

    // Basic validation
    if (empty($shipping_name) || empty($shipping_phone) || empty($shipping_address) || empty($payment_method)) {
        $errors[] = "Please fill in all shipping and payment details.";
    }

    if ($payment_method !== 'cod' && (empty($payment_sender_no) || empty($payment_trx_id))) {
        $errors[] = "Please provide your Sender Number and Transaction ID for the selected payment method.";
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            // Updated INSERT statement to include shipping_fee
            $order_stmt = $pdo->prepare(
                "INSERT INTO orders (user_id, total_amount, status, payment_method, payment_trx_id, payment_sender_no, shipping_fee) VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $order_stmt->execute([$user_id, $final_grand_total, 'Pending', $payment_method, $payment_trx_id, $payment_sender_no, $final_shipping_fee]);
            $order_id = $pdo->lastInsertId();

            $order_item_stmt = $pdo->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)"
            );
            $update_stock_stmt = $pdo->prepare(
                "UPDATE products SET stock = stock - ? WHERE id = ?"
            );

            foreach ($products_in_cart as $product) {
                $quantity = $cart_items[$product['id']];
                $order_item_stmt->execute([$order_id, $product['id'], $quantity, $product['price']]);
                $update_stock_stmt->execute([$quantity, $product['id']]);
            }

            $pdo->commit();

            unset($_SESSION['cart']);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Your order has been placed successfully!'];
            redirect('order-details?id=' . $order_id);

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Your order could not be placed due to a system error. Please try again.";
        }
    }
}

$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>
<!-- Custom CSS for Payment Method Selection -->
<style>
    .payment-method-box, .shipping-location-box {
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
    }
    .payment-method-box:hover, .shipping-location-box:hover {
        border-color: #0d6efd;
    }
    .payment-method-box.active, .shipping-location-box.active {
        border-color: #0d6efd;
        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
    }
    .payment-details {
        display: none;
        background-color: #f8f9fa;
        border-radius: 0.5rem;
        margin-top: 1rem;
        padding: 1rem;
    }
    .payment-details.show {
        display: block;
    }
    .payment-logo {
        height: 25px;
        margin-right: 10px;
    }
</style>

<div class="page-header" style="background-color: #f8f9fa; padding: 2rem 0; margin-bottom: 3rem;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index">Home</a></li>
                <li class="breadcrumb-item"><a href="cart">Cart</a></li>
                <li class="breadcrumb-item active" aria-current="page">Checkout</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold mt-2">Checkout</h1>
    </div>
</div>

<main class="container my-5">
    <form action="checkout" method="post" id="checkoutForm">
        <div class="row g-5">
            <!-- Shipping Details Column -->
            <div class="col-md-7 col-lg-8">
                <h4 class="mb-3">Shipping Address</h4>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= esc_html($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-12">
                        <label for="fullName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="fullName" name="full_name" value="<?= esc_html($user['username']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= esc_html($user['phone']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required><?= esc_html($user['address']) ?></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Shipping Location -->
                <h4 class="mb-3">Shipping Location</h4>
                <div class="row g-3" id="shippingLocation">
                    <div class="col-md-6">
                        <div class="shipping-location-box active" data-location="dhaka">
                            <input id="dhaka" name="shipping_location" type="radio" class="form-check-input" value="dhaka" checked>
                            <label class="form-check-label fw-bold ms-2" for="dhaka">Inside Dhaka (<?= formatPrice($settings['shipping_fee_dhaka'] ?? 60) ?>)</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="shipping-location-box" data-location="outside">
                            <input id="outside" name="shipping_location" type="radio" class="form-check-input" value="outside">
                            <label class="form-check-label fw-bold ms-2" for="outside">Outside Dhaka (<?= formatPrice($settings['shipping_fee_outside'] ?? 120) ?>)</label>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <h4 class="mb-3">Payment Method</h4>
                <div class="row g-3" id="paymentMethods">
                    <!-- Cash on Delivery -->
                    <div class="col-md-6">
                        <div class="payment-method-box active" data-payment="cod">
                            <input id="cod" name="payment_method" type="radio" class="form-check-input" value="cod" checked>
                            <label class="form-check-label fw-bold ms-2" for="cod">Cash on Delivery</label>
                        </div>
                    </div>
                    <!-- bKash -->
                    <?php if(!empty($settings['bkash_number'])): ?>
                        <div class="col-md-6">
                            <div class="payment-method-box" data-payment="bkash">
                                <input id="bkash" name="payment_method" type="radio" class="form-check-input" value="bkash">
                                <label class="form-check-label fw-bold ms-2" for="bkash">
                                    <img src="/assets/images/bkash.svg" alt="bKash" class="payment-logo"> bKash
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <!-- Nagad -->
                    <?php if(!empty($settings['nagad_number'])): ?>
                        <div class="col-md-6">
                            <div class="payment-method-box" data-payment="nagad">
                                <input id="nagad" name="payment_method" type="radio" class="form-check-input" value="nagad">
                                <label class="form-check-label fw-bold ms-2" for="nagad">
                                    <img src="/assets/images/nagad.svg" alt="Nagad" class="payment-logo"> Nagad
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <!-- Rocket -->
                    <?php if(!empty($settings['rocket_number'])): ?>
                        <div class="col-md-6">
                            <div class="payment-method-box" data-payment="rocket">
                                <input id="rocket" name="payment_method" type="radio" class="form-check-input" value="rocket">
                                <label class="form-check-label fw-bold ms-2" for="rocket">
                                    <img src="/assets/images/rocket.png" alt="Rocket" class="payment-logo"> Rocket
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Hidden details form for Mobile Payments -->
                <div class="payment-details mt-3" id="mobilePaymentDetails">
                    <p class="mb-2">Please send <strong><span id="paymentAmount"><?= formatPrice($grand_total) ?></span></strong> to our account number below and enter your details.</p>
                    <p class="h5 text-center text-primary fw-bold bg-white p-2 rounded" id="paymentNumberDisplay"></p>
                    <hr>
                    <div class="mb-3">
                        <label for="paymentSenderNo" class="form-label">Your Sender Number</label>
                        <input type="text" class="form-control" id="paymentSenderNo" name="payment_sender_no" placeholder="e.g., 01xxxxxxxxx">
                    </div>
                    <div class="mb-3">
                        <label for="paymentTrxId" class="form-label">Transaction ID (TrxID)</label>
                        <input type="text" class="form-control" id="paymentTrxId" name="payment_trx_id" placeholder="e.g., 9J7K3L2M1N">
                    </div>
                </div>
            </div>

            <!-- Order Summary Column -->
            <div class="col-md-5 col-lg-4 order-md-last">
                <h4 class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-primary">Your Cart</span>
                    <span class="badge bg-primary rounded-pill"><?= count($products_in_cart) ?></span>
                </h4>
                <ul class="list-group mb-3">
                    <?php foreach ($products_in_cart as $product): ?>
                        <li class="list-group-item d-flex justify-content-between lh-sm">
                            <div>
                                <h6 class="my-0"><?= esc_html($product['name']) ?></h6>
                                <small class="text-muted">Quantity: <?= $cart_items[$product['id']] ?></small>
                            </div>
                            <span class="text-muted"><?= formatPrice($product['price'] * $cart_items[$product['id']]) ?></span>
                        </li>
                    <?php endforeach; ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Subtotal</span>
                        <strong id="summarySubtotal"><?= formatPrice($subtotal) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Shipping</span>
                        <strong id="summaryShipping"><?= formatPrice($shipping_fee) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between bg-light">
                        <span class="fw-bold">Total (BDT)</span>
                        <strong class="fw-bold" id="summaryTotal"><?= formatPrice($grand_total) ?></strong>
                    </li>
                </ul>
                <div class="d-grid">
                    <button class="btn btn-primary btn-lg" type="submit">Place Order</button>
                </div>
            </div>
        </div>
    </form>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Store settings from PHP to JS
        const settings = {
            shipping_dhaka: <?= (float)($settings['shipping_fee_dhaka'] ?? 60) ?>,
            shipping_outside: <?= (float)($settings['shipping_fee_outside'] ?? 120) ?>,
            bkash: '<?= esc_html($settings['bkash_number'] ?? '') ?>',
            nagad: '<?= esc_html($settings['nagad_number'] ?? '') ?>',
            rocket: '<?= esc_html($settings['rocket_number'] ?? '') ?>'
        };
        const subtotal = <?= (float)$subtotal ?>;

        // DOM Elements
        const shippingLocationContainer = document.getElementById('shippingLocation');
        const paymentMethodsContainer = document.getElementById('paymentMethods');
        const mobilePaymentDetails = document.getElementById('mobilePaymentDetails');
        const paymentNumberDisplay = document.getElementById('paymentNumberDisplay');
        const senderNoInput = document.getElementById('paymentSenderNo');
        const trxIdInput = document.getElementById('paymentTrxId');

        // Summary DOM Elements
        const summaryShipping = document.getElementById('summaryShipping');
        const summaryTotal = document.getElementById('summaryTotal');
        const paymentAmount = document.getElementById('paymentAmount');

        function formatPriceJS(price) {
            return '৳' + price.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function updateTotals() {
            const selectedLocation = document.querySelector('input[name="shipping_location"]:checked').value;
            const shippingFee = (selectedLocation === 'outside') ? settings.shipping_outside : settings.shipping_dhaka;
            const grandTotal = subtotal + shippingFee;

            summaryShipping.textContent = formatPriceJS(shippingFee);
            summaryTotal.textContent = formatPriceJS(grandTotal);
            paymentAmount.textContent = formatPriceJS(grandTotal);
        }

        // --- Event Listeners ---

        // Shipping Location Change
        shippingLocationContainer.addEventListener('click', function(e) {
            let targetBox = e.target.closest('.shipping-location-box');
            if (!targetBox) return;

            document.querySelectorAll('.shipping-location-box').forEach(box => box.classList.remove('active'));
            targetBox.classList.add('active');
            targetBox.querySelector('input[type="radio"]').checked = true;

            updateTotals();
        });

        // Payment Method Change
        paymentMethodsContainer.addEventListener('click', function(e) {
            let targetBox = e.target.closest('.payment-method-box');
            if (!targetBox) return;

            document.querySelectorAll('.payment-method-box').forEach(box => box.classList.remove('active'));
            targetBox.classList.add('active');
            targetBox.querySelector('input[type="radio"]').checked = true;

            const paymentType = targetBox.dataset.payment;

            if (paymentType === 'cod') {
                mobilePaymentDetails.classList.remove('show');
                senderNoInput.required = false;
                trxIdInput.required = false;
            } else {
                mobilePaymentDetails.classList.add('show');
                paymentNumberDisplay.textContent = settings[paymentType] || 'N/A';
                senderNoInput.required = true;
                trxIdInput.required = true;
            }
        });

        // Set initial state
        updateTotals();
        document.querySelector('.shipping-location-box[data-location="dhaka"]').classList.add('active');
        document.querySelector('.payment-method-box[data-payment="cod"]').classList.add('active');
    });
</script>

<?php include 'includes/footer.php'; ?>

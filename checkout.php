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


// --- DATA FETCHIG for both GET and POST ---

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

<!-- Modern Checkout Styles -->
<style>
    .checkout-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
        color: white;
        padding: 3rem 0;
        position: relative;
        overflow: hidden;
    }

    .checkout-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="checkout-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23checkout-pattern)"/></svg>');
        opacity: 0.1;
    }

    .checkout-header-content {
        position: relative;
        z-index: 2;
    }

    .checkout-container {
        padding: 2rem 0;
        min-height: 60vh;
    }

    .checkout-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border-color);
        transition: var(--transition);
    }

    .checkout-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--dark-color);
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 3px solid var(--primary-color);
        position: relative;
    }

    .section-title::before {
        content: '';
        position: absolute;
        bottom: -3px;
        left: 0;
        width: 60px;
        height: 3px;
        background: var(--primary-hover);
    }

    .custom-form-control {
        border: 2px solid var(--border-color);
        border-radius: 12px;
        padding: 0.875rem 1rem;
        transition: var(--transition);
        font-size: 1rem;
        background: var(--secondary-color);
    }

    .custom-form-control:focus {
        border-color: var(--primary-color);
        background: white;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        outline: none;
    }

    .custom-form-label {
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }

    .shipping-location-box,
    .payment-method-box {
        border: 2px solid var(--border-color);
        border-radius: 15px;
        padding: 1.25rem;
        cursor: pointer;
        transition: var(--transition);
        background: white;
        position: relative;
        overflow: hidden;
    }

    .shipping-location-box::before,
    .payment-method-box::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.05), transparent);
        transition: var(--transition);
    }

    .shipping-location-box:hover::before,
    .payment-method-box:hover::before {
        left: 100%;
    }

    .shipping-location-box:hover,
    .payment-method-box:hover {
        border-color: var(--primary-color);
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    .shipping-location-box.active,
    .payment-method-box.active {
        border-color: var(--primary-color);
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(99, 102, 241, 0.02));
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        transform: translateY(-2px);
    }

    .payment-details {
        display: none;
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        border: 2px solid var(--primary-color);
        border-radius: 15px;
        margin-top: 1.5rem;
        padding: 1.5rem;
        position: relative;
    }

    .payment-details::before {
        content: '';
        position: absolute;
        top: -10px;
        left: 20px;
        width: 20px;
        height: 20px;
        background: var(--primary-color);
        transform: rotate(45deg);
    }

    .payment-details.show {
        display: block;
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .payment-logo {
        height: 30px;
        width: auto;
        margin-right: 12px;
        border-radius: 6px;
    }

    .payment-number-display {
        background: var(--primary-color);
        color: white;
        padding: 1rem;
        border-radius: 12px;
        font-size: 1.25rem;
        font-weight: 700;
        text-align: center;
        border: 3px solid var(--primary-hover);
        box-shadow: var(--shadow-md);
    }

    .order-summary-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border-color);
        position: sticky;
        top: 100px;
    }

    .cart-item-mini {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: var(--secondary-color);
        border-radius: 12px;
        margin-bottom: 1rem;
        border: 1px solid var(--border-color);
    }

    .cart-item-mini img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid var(--border-color);
    }

    .cart-item-details h6 {
        font-weight: 600;
        color: var(--dark-color);
        margin: 0 0 0.25rem 0;
        font-size: 0.95rem;
    }

    .cart-item-details small {
        color: var(--light-text);
        font-size: 0.85rem;
    }

    .cart-item-price {
        font-weight: 700;
        color: var(--primary-color);
        font-size: 1rem;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border-color);
    }

    .summary-row:last-child {
        border-bottom: none;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
        color: white;
        padding: 1rem;
        border-radius: 12px;
        margin-top: 1rem;
        font-size: 1.1rem;
        font-weight: 700;
    }

    .place-order-btn {
        width: 100%;
        background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
        color: white;
        border: none;
        padding: 1.25rem 2rem;
        border-radius: 15px;
        font-weight: 700;
        font-size: 1.1rem;
        margin-top: 1.5rem;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .place-order-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: var(--transition);
    }

    .place-order-btn:hover::before {
        left: 100%;
    }

    .place-order-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(99, 102, 241, 0.4);
    }

    .progress-steps {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 3rem;
        gap: 1rem;
    }

    .step {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--border-color);
        color: var(--light-text);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        transition: var(--transition);
    }

    .step.active .step-circle {
        background: var(--primary-color);
        color: white;
    }

    .step.completed .step-circle {
        background: #10b981;
        color: white;
    }

    .step-line {
        width: 60px;
        height: 2px;
        background: var(--border-color);
        margin: 0 1rem;
    }

    .step.completed .step-line {
        background: #10b981;
    }

    .alert-custom {
        border-radius: 15px;
        border: none;
        padding: 1.25rem 1.5rem;
        margin-bottom: 2rem;
        border-left: 5px solid #dc3545;
    }

    .breadcrumb-custom {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        padding: 1rem 1.5rem;
        border-radius: 15px;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .breadcrumb-custom .breadcrumb {
        margin: 0;
    }

    .breadcrumb-custom .breadcrumb-item a {
        color: white;
        text-decoration: none;
        font-weight: 500;
    }

    .breadcrumb-custom .breadcrumb-item.active {
        color: rgba(255, 255, 255, 0.8);
    }

    /* Form Validation Styles */
    .custom-form-control.is-invalid {
        border-color: #dc3545;
        background-color: #fff5f5;
    }

    .custom-form-control.is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.1);
    }

    /* Loading State for Submit Button */
    .place-order-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none !important;
    }

    .place-order-btn:disabled:hover {
        transform: none !important;
        box-shadow: var(--shadow-lg) !important;
    }

    /* Notification Styles */
    .checkout-notification {
        animation: slideInRight 0.3s ease-out;
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100%);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @media (max-width: 768px) {
        .checkout-card {
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .order-summary-card {
            position: static;
            margin-top: 2rem;
        }

        .cart-item-mini {
            flex-direction: column;
            text-align: center;
            gap: 0.5rem;
        }

        .progress-steps {
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .step-line {
            width: 30px;
        }

        .checkout-notification {
            right: 10px !important;
            left: 10px !important;
            max-width: none !important;
        }
    }
</style>





<!-- Main Checkout Content -->
<section class="checkout-container">
    <div class="container">
        <form action="checkout" method="post" id="checkoutForm">
            <div class="row g-4">
                <!-- Shipping Details Column -->
                <div class="col-lg-8">

                    <!-- Error Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-custom">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong>Please fix the following issues:</strong>
                            </div>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= esc_html($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Shipping Address Card -->
                    <div class="checkout-card">
                        <h4 class="section-title">
                            <i class="bi bi-truck me-2"></i>Shipping Address
                        </h4>

                        <div class="row g-3">
                            <div class="col-12 d-flex flex-column">
                                <label for="fullName" class="custom-form-label">
                                    <i class="bi bi-person me-1"></i>Full Name
                                </label>
                                <input type="text"
                                    class="custom-form-control"
                                    id="fullName"
                                    name="full_name"
                                    value="<?= esc_html($user['username']) ?>"
                                    placeholder="Enter your full name"
                                    required>
                            </div>
                            <div class="col-12 d-flex flex-column">
                                <label for="phone" class="custom-form-label">
                                    <i class="bi bi-telephone me-1"></i>Phone Number
                                </label>
                                <input type="tel"
                                    class="custom-form-control"
                                    id="phone"
                                    name="phone"
                                    value="<?= esc_html($user['phone']) ?>"
                                    placeholder="01XXXXXXXXX"
                                    required>
                            </div>
                            <div class="col-12 d-flex flex-column">
                                <label for="email" class="custom-form-label">
                                    <i class="bi bi-envelope me-1"></i>Email (Optional)
                                </label>
                                <input type="email"
                                    class="custom-form-control"
                                    id="email"
                                    name="email"
                                    value="<?= esc_html($user['email'] ?? '') ?>"
                                    placeholder="your@email.com">
                            </div>
                            <div class="col-12 d-flex flex-column">
                                <label for="address" class="custom-form-label">
                                    <i class="bi bi-geo-alt me-1"></i>Complete Address
                                </label>
                                <textarea class="custom-form-control"
                                    id="address"
                                    name="address"
                                    rows="3"
                                    placeholder="House/Flat, Road, Area, City"
                                    required><?= esc_html($user['address']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Location Card -->
                    <div class="checkout-card">
                        <h4 class="section-title">
                            <i class="bi bi-geo-alt me-2"></i>Shipping Location
                        </h4>
                        <div class="row g-3" id="shippingLocation">
                            <div class="col-md-6">
                                <div class="shipping-location-box active" data-location="dhaka">
                                    <div class="d-flex align-items-center">
                                        <input id="dhaka" name="shipping_location" type="radio" class="form-check-input me-3" value="dhaka" checked>
                                        <div>
                                            <label class="form-check-label fw-bold d-block" for="dhaka">Inside Dhaka</label>
                                            <small class="text-muted">Delivery: 1-2 Days</small>
                                            <div class="mt-1">
                                                <span class="badge bg-primary"><?= formatPrice($settings['shipping_fee_dhaka'] ?? 60) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="shipping-location-box" data-location="outside">
                                    <div class="d-flex align-items-center">
                                        <input id="outside" name="shipping_location" type="radio" class="form-check-input me-3" value="outside">
                                        <div>
                                            <label class="form-check-label fw-bold d-block" for="outside">Outside Dhaka</label>
                                            <small class="text-muted">Delivery: 3-5 Days</small>
                                            <div class="mt-1">
                                                <span class="badge bg-secondary"><?= formatPrice($settings['shipping_fee_outside'] ?? 120) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Card -->
                    <div class="checkout-card">
                        <h4 class="section-title">
                            <i class="bi bi-credit-card me-2"></i>Payment Method
                        </h4>
                        <div class="row g-3" id="paymentMethods">
                            <!-- Cash on Delivery -->
                            <div class="col-md-6">
                                <div class="payment-method-box active" data-payment="cod">
                                    <div class="d-flex align-items-center">
                                        <input id="cod" name="payment_method" type="radio" class="form-check-input me-3" value="cod" checked>
                                        <div>
                                            <label class="form-check-label fw-bold d-block" for="cod">
                                                <i class="bi bi-cash-coin me-2"></i>Cash on Delivery
                                            </label>
                                            <small class="text-muted">Pay when you receive</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- bKash -->
                            <?php if (!empty($settings['bkash_number'])): ?>
                                <div class="col-md-6">
                                    <div class="payment-method-box" data-payment="bkash">
                                        <div class="d-flex align-items-center">
                                            <input id="bkash" name="payment_method" type="radio" class="form-check-input me-3" value="bkash">
                                            <div>
                                                <label class="form-check-label fw-bold d-block" for="bkash">
                                                    <img src="assets/images/bkash.svg" alt="bKash" class="payment-logo"> bKash
                                                </label>
                                                <small class="text-muted">Mobile payment</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <!-- Nagad -->
                            <?php if (!empty($settings['nagad_number'])): ?>
                                <div class="col-md-6">
                                    <div class="payment-method-box" data-payment="nagad">
                                        <div class="d-flex align-items-center">
                                            <input id="nagad" name="payment_method" type="radio" class="form-check-input me-3" value="nagad">
                                            <div>
                                                <label class="form-check-label fw-bold d-block" for="nagad">
                                                    <img src="assets/images/nagad.svg" alt="Nagad" class="payment-logo"> Nagad
                                                </label>
                                                <small class="text-muted">Mobile payment</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <!-- Rocket -->
                            <?php if (!empty($settings['rocket_number'])): ?>
                                <div class="col-md-6">
                                    <div class="payment-method-box" data-payment="rocket">
                                        <div class="d-flex align-items-center">
                                            <input id="rocket" name="payment_method" type="radio" class="form-check-input me-3" value="rocket">
                                            <div>
                                                <label class="form-check-label fw-bold d-block" for="rocket">
                                                    <img src="assets/images/rocket.png" alt="Rocket" class="payment-logo"> Rocket
                                                </label>
                                                <small class="text-muted">Mobile payment</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Mobile Payment Details -->
                        <div class="payment-details" id="mobilePaymentDetails">
                            <div class="text-center mb-3">
                                <h6>
                                    <i class="bi bi-info-circle me-2"></i>
                                    Payment Instructions
                                </h6>
                                <p class="mb-3">Send <strong><span id="paymentAmount"><?= formatPrice($grand_total) ?></span></strong> to the number below</p>
                            </div>

                            <div class="payment-number-display" id="paymentNumberDisplay"></div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-6">
                                    <label for="paymentSenderNo" class="custom-form-label">
                                        <i class="bi bi-phone me-1"></i>Your Sender Number
                                    </label>
                                    <input type="text"
                                        class="custom-form-control"
                                        id="paymentSenderNo"
                                        name="payment_sender_no"
                                        placeholder="01XXXXXXXXX">
                                </div>
                                <div class="col-md-6">
                                    <label for="paymentTrxId" class="custom-form-label">
                                        <i class="bi bi-receipt me-1"></i>Transaction ID
                                    </label>
                                    <input type="text"
                                        class="custom-form-control"
                                        id="paymentTrxId"
                                        name="payment_trx_id"
                                        placeholder="e.g., 9J7K3L2M1N">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary Column -->
                <div class="col-lg-4">
                    <div class="order-summary-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="section-title mb-0">
                                <i class="bi bi-receipt me-2"></i>Order Summary
                            </h4>
                            <span class="badge bg-primary rounded-pill"><?= count($products_in_cart) ?></span>
                        </div>

                        <!-- Cart Items -->
                        <div class="cart-items-list mb-4">
                            <?php foreach ($products_in_cart as $product): ?>
                                <div class="cart-item-mini">
                                    <img src="admin/assets/uploads/<?= esc_html($product['image']) ?>"
                                        alt="<?= esc_html($product['name']) ?>">
                                    <div class="cart-item-details flex-grow-1">
                                        <h6><?= esc_html($product['name']) ?></h6>
                                        <small>Qty: <?= $cart_items[$product['id']] ?></small>
                                    </div>
                                    <div class="cart-item-price">
                                        <?= formatPrice($product['price'] * $cart_items[$product['id']]) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Summary Totals -->
                        <div class="summary-section">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <strong id="summarySubtotal"><?= formatPrice($subtotal) ?></strong>
                            </div>
                            <div class="summary-row">
                                <span>Shipping</span>
                                <strong id="summaryShipping"><?= formatPrice($shipping_fee) ?></strong>
                            </div>
                            <div class="summary-row">
                                <span class="fw-bold">Total</span>
                                <strong class="fw-bold" id="summaryTotal"><?= formatPrice($grand_total) ?></strong>
                            </div>
                        </div>

                        <!-- Place Order Button -->
                        <button class="place-order-btn" type="submit">
                            <i class="bi bi-shield-check me-2"></i>
                            Place Secure Order
                        </button>

                        <!-- Security Notice -->
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="bi bi-lock me-1"></i>
                                Your information is secure and encrypted
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Enhanced Checkout JavaScript -->
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
        const checkoutForm = document.getElementById('checkoutForm');

        // Summary DOM Elements
        const summaryShipping = document.getElementById('summaryShipping');
        const summaryTotal = document.getElementById('summaryTotal');
        const paymentAmount = document.getElementById('paymentAmount');

        function formatPriceJS(price) {
            return '৳' + price.toLocaleString('en-BD', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function updateTotals() {
            const selectedLocation = document.querySelector('input[name="shipping_location"]:checked').value;
            const shippingFee = (selectedLocation === 'outside') ? settings.shipping_outside : settings.shipping_dhaka;
            const grandTotal = subtotal + shippingFee;

            summaryShipping.textContent = formatPriceJS(shippingFee);
            summaryTotal.textContent = formatPriceJS(grandTotal);
            if (paymentAmount) {
                paymentAmount.textContent = formatPriceJS(grandTotal);
            }
        }

        function showNotification(type, message) {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.checkout-notification');
            existingNotifications.forEach(n => n.remove());

            // Create notification
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show checkout-notification`;
            notification.style.cssText = 'position: fixed; top: 100px; right: 20px; z-index: 9999; max-width: 350px;';

            const icon = type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'x-circle';
            notification.innerHTML = `
            <i class="bi bi-${icon} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

            document.body.appendChild(notification);

            // Auto-remove after 4 seconds
            setTimeout(() => {
                if (notification && notification.parentNode) {
                    notification.remove();
                }
            }, 4000);
        }

        function validateForm() {
            let isValid = true;
            const requiredFields = checkoutForm.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                field.classList.remove('is-invalid');
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                }
            });

            // Phone number validation
            const phoneInput = document.getElementById('phone');
            const phonePattern = /^01[0-9]{9}$/;
            if (phoneInput.value && !phonePattern.test(phoneInput.value)) {
                phoneInput.classList.add('is-invalid');
                showNotification('error', 'Please enter a valid phone number (01XXXXXXXXX)');
                isValid = false;
            }

            // Payment method specific validation
            const selectedPayment = document.querySelector('input[name="payment_method"]:checked').value;
            if (selectedPayment !== 'cod') {
                if (!senderNoInput.value.trim() || !trxIdInput.value.trim()) {
                    showNotification('error', 'Please provide sender number and transaction ID for mobile payment');
                    isValid = false;
                }
            }

            return isValid;
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

            // Show feedback
            const location = targetBox.dataset.location;
            const message = location === 'dhaka' ? 'Delivery within Dhaka (1-2 days)' : 'Delivery outside Dhaka (3-5 days)';
            showNotification('success', message);
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
                showNotification('success', 'Cash on Delivery selected - Pay when you receive your order');
            } else {
                mobilePaymentDetails.classList.add('show');
                paymentNumberDisplay.textContent = settings[paymentType] || 'N/A';
                senderNoInput.required = true;
                trxIdInput.required = true;

                const paymentName = paymentType.charAt(0).toUpperCase() + paymentType.slice(1);
                showNotification('info', `${paymentName} payment selected - Please complete the payment first`);
            }
        });

        // Form submission validation
        checkoutForm.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return false;
            }

            // Show loading state
            const submitBtn = checkoutForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="bi bi-clock me-2"></i>Processing Order...';
            submitBtn.disabled = true;

            // Re-enable if there's an error (will reload if successful)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });

        // Real-time validation
        const inputs = checkoutForm.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                this.classList.remove('is-invalid');
                if (this.required && !this.value.trim()) {
                    this.classList.add('is-invalid');
                }
            });

            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });

        // Set initial state
        updateTotals();

        // Initialize form state
        const initialLocation = document.querySelector('.shipping-location-box[data-location="dhaka"]');
        const initialPayment = document.querySelector('.payment-method-box[data-payment="cod"]');

        if (initialLocation) initialLocation.classList.add('active');
        if (initialPayment) initialPayment.classList.add('active');
    });
</script>

<?php include 'includes/footer.php'; ?>
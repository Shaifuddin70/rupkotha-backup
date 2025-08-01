<?php
// This is the order details page, e.g., order-details

// STEP 1: Start session and include necessary files FIRST.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/functions.php';

// STEP 2: Authentication and Authorization Checks.
// Redirect if not logged in.
if (!isLoggedIn()) {
    redirect('login?redirect=profile');
}

// Get Order ID from URL and validate it.
$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$order_id) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid order ID.'];
    redirect('profile');
}

$user_id = $_SESSION['user_id'];

// --- DATA FETCHING ---

// 1. Fetch the main order details and verify ownership.
// This is a CRITICAL security check to ensure a user can only see their own orders.
$order_stmt = $pdo->prepare(
    "SELECT o.*, u.username, u.email, u.phone, u.address 
     FROM orders o
     JOIN users u ON o.user_id = u.id
     WHERE o.id = :order_id AND o.user_id = :user_id"
);
$order_stmt->execute([':order_id' => $order_id, ':user_id' => $user_id]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

// If order doesn't exist or doesn't belong to the user, redirect them.
if (!$order) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Order not found or you do not have permission to view it.'];
    redirect('profile');
}

// 2. Fetch the items associated with this order.
$order_items_stmt = $pdo->prepare(
    "SELECT oi.quantity, oi.price, p.name, p.image 
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = :order_id"
);
$order_items_stmt->execute([':order_id' => $order_id]);
$order_items = $order_items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate subtotal from items (more accurate than just storing total)
$subtotal = 0;
foreach ($order_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
// Assuming a fixed shipping cost was used, as in checkout.php
$shipping_cost = $order['total_amount'] - $subtotal;


// STEP 3: Now, include the header.
include 'includes/header.php';
?>



<div class="page-header" style="background-color: #f8f9fa; padding: 2rem 0; margin-bottom: 3rem;">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index">Home</a></li>
                <li class="breadcrumb-item"><a href="profile">My Account</a></li>
                <li class="breadcrumb-item active" aria-current="page">Order Details</li>
            </ol>
        </nav>
        <h1 class="display-5 fw-bold mt-2">Order #<?= esc_html($order['id']) ?></h1>
    </div>
</div>

<main class="container my-5">
    <!-- Add a wrapper div for the printable area -->
    <div class="invoice-area">
        <div class="card shadow-sm">
            <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Order Details</h5>
                <button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer-fill me-2"></i>Print</button>
            </div>
            <div class="card-body p-4">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <h6>Order Information</h6>
                        <p class="mb-1"><strong>Order ID:</strong> #<?= esc_html($order['id']) ?></p>
                        <p class="mb-1"><strong>Date:</strong> <?= format_date($order['created_at']) ?></p>
                        <p class="mb-1"><strong>Status:</strong>
                            <span class="badge <?= $order['status'] === 'Completed' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                <?= esc_html($order['status']) ?>
                            </span>
                        </p>
                    </div>
                    <div class="col-md-8">
                        <h6>Shipping Address:</h6>
                        <p class="mb-1"> Name: <strong><?= esc_html($order['username']) ?></strong></p>
                        <p class="mb-1">Address: <?= esc_html($order['address']) ?></p>
                        <p class="mb-1">Phone: <?= esc_html($order['phone']) ?></p>
                        <p class="mb-1">Email: <?= esc_html($order['email']) ?></p>
                    </div>
                </div>

                <h5 class="mb-3">Items Ordered</h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col" class="border-0 bg-light" style="width: 100px;">
                                <div class="p-2 text-uppercase">Product</div>
                            </th>
                            <th scope="col" class="border-0 bg-light">
                                <div class="py-2 text-uppercase">Description</div>
                            </th>
                            <th scope="col" class="border-0 bg-light">
                                <div class="py-2 text-uppercase">Price</div>
                            </th>
                            <th scope="col" class="border-0 bg-light">
                                <div class="py-2 text-uppercase">Quantity</div>
                            </th>
                            <th scope="col" class="border-0 bg-light">
                                <div class="py-2 text-uppercase">Total</div>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td>
                                    <img src="admin/assets/uploads/<?= esc_html($item['image']) ?>" alt="<?= esc_html($item['name']) ?>" width="80" class="img-fluid rounded">
                                </td>
                                <td class="align-middle">
                                    <strong class="d-block"> <?= esc_html($item['name']) ?></strong>
                                </td>
                                <td class="align-middle"><?= formatPrice($item['price']) ?></td>
                                <td class="align-middle"><?= esc_html($item['quantity']) ?></td>
                                <td class="align-middle fw-bold"><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <hr class="my-4">

                <div class="row justify-content-end">
                    <div class="col-lg-4">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 pb-0">
                                Subtotal
                                <span><?= formatPrice($subtotal) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                Shipping
                                <span><?= formatPrice($shipping_cost) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center border-0 px-0 mb-3 fs-5 fw-bold">
                                <span>Grand Total</span>
                                <span><?= formatPrice($order['total_amount']) ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-4">
        <a href="profile#v-pills-orders-tab" class="btn btn-primary"><i class="bi bi-arrow-left me-2"></i>Back to Order History</a>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

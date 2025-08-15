<?php
// Cart Page - Shopping Cart Management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'includes/functions.php';

// Include header
require_once 'includes/header.php';

// Get cart items from session
$cart_items = $_SESSION['cart'] ?? [];
$products_in_cart = [];
$subtotal = 0;
$errors = [];

// If cart is not empty, fetch product details
if (!empty($cart_items)) {
    $product_ids = array_keys($cart_items);
    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));

    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
        $stmt->execute($product_ids);
        $products_from_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Create a map for easier access
        $product_map = [];
        foreach ($products_from_db as $p) {
            $product_map[$p['id']] = $p;
        }

        // Prepare cart data and calculate totals
        foreach ($cart_items as $product_id => $quantity) {
            if (isset($product_map[$product_id])) {
                $product = $product_map[$product_id];
                $product['cart_quantity'] = $quantity;
                $product['line_total'] = $product['price'] * $quantity;
                
                // Check stock availability
                if ($quantity > $product['stock']) {
                    $errors[] = "Only " . $product['stock'] . " units of '" . esc_html($product['name']) . "' are available.";
                }
                
                $products_in_cart[] = $product;
                $subtotal += $product['line_total'];
            }
        }
    } catch (PDOException $e) {
        $errors[] = "Error loading cart items. Please try again.";
    }
}

// Handle flash messages
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>

<!-- Custom Cart Styles -->
<style>
.cart-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
    color: white;
    padding: 3rem 0;
    position: relative;
    overflow: hidden;
}

.cart-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="cart-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23cart-pattern)"/></svg>');
    opacity: 0.1;
}

.cart-header-content {
    position: relative;
    z-index: 2;
}

.cart-container {
    min-height: 60vh;
    padding: 2rem 0;
}

.cart-item {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    transition: var(--transition);
}

.cart-item:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.cart-item-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid var(--border-color);
}

.cart-item-details h5 {
    font-weight: 600;
    color: var(--dark-color);
    margin-bottom: 0.5rem;
}

.cart-item-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-color);
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 1rem 0;
}

.quantity-btn {
    width: 35px;
    height: 35px;
    border: 2px solid var(--border-color);
    background: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: var(--text-color);
    transition: var(--transition);
    cursor: pointer;
}

.quantity-btn:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
    background: rgba(99, 102, 241, 0.05);
}

.quantity-input {
    width: 60px;
    height: 35px;
    text-align: center;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-weight: 600;
    color: var(--text-color);
}

.quantity-input:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.remove-item-btn {
    background: #ef4444;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: var(--transition);
}

.remove-item-btn:hover {
    background: #dc2626;
    transform: translateY(-1px);
}

.cart-summary {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
    position: sticky;
    top: 100px;
}

.summary-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--border-color);
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
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-color);
}

.checkout-btn {
    width: 100%;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1.1rem;
    margin-top: 1.5rem;
    transition: var(--transition);
}

.checkout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
}

.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 15px;
    box-shadow: var(--shadow-sm);
}

.empty-cart-icon {
    font-size: 4rem;
    color: var(--light-text);
    margin-bottom: 1rem;
}

.continue-shopping-btn {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: var(--transition);
}

.continue-shopping-btn:hover {
    background: var(--primary-hover);
    color: white;
    transform: translateY(-2px);
}

.alert-custom {
    border-radius: 12px;
    border: none;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
}

.stock-warning {
    background: #fef3cd;
    color: #856404;
    border-left: 4px solid #ffc107;
}

@media (max-width: 768px) {
    .cart-item {
        padding: 1rem;
    }
    
    .cart-item-image {
        width: 80px;
        height: 80px;
    }
    
    .cart-summary {
        position: static;
        margin-top: 2rem;
    }
    
    .quantity-controls {
        justify-content: center;
    }
}
</style>



<!-- Main Cart Content -->
<section class="cart-container">
    <div class="container">
        
        <?php if ($flash_message): ?>
            <div class="alert alert-<?= $flash_message['type'] ?> alert-custom alert-dismissible fade show" role="alert">
                <i class="bi bi-<?= $flash_message['type'] === 'success' ? 'check-circle' : ($flash_message['type'] === 'warning' ? 'exclamation-triangle' : 'x-circle') ?> me-2"></i>
                <?= esc_html($flash_message['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert-custom stock-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?= esc_html($error) ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (empty($products_in_cart)): ?>
            <!-- Empty Cart -->
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="bi bi-cart-x"></i>
                </div>
                <h3 class="mb-3">Your cart is empty</h3>
                <p class="text-muted mb-4">Looks like you haven't added any items to your cart yet. Start shopping to fill it up!</p>
                <a href="all-products" class="continue-shopping-btn">
                    <i class="bi bi-arrow-left me-2"></i>Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <!-- Cart Items -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="fw-bold" id="cart-items-header">Cart Items (<?= count($products_in_cart) ?>)</h3>
                        <a href="all-products" class="continue-shopping-btn">
                            <i class="bi bi-plus-circle me-2"></i>Add More Items
                        </a>
                    </div>

                    <?php foreach ($products_in_cart as $product): ?>
                        <div class="cart-item" data-product-id="<?= $product['id'] ?>">
                            <div class="row align-items-center">
                                <div class="col-md-3 col-4">
                                    <img src="admin/assets/uploads/<?= esc_html($product['image']) ?>" 
                                         alt="<?= esc_html($product['name']) ?>" 
                                         class="cart-item-image">
                                </div>
                                <div class="col-md-6 col-8">
                                    <div class="cart-item-details">
                                        <h5><?= esc_html($product['name']) ?></h5>
                                        <p class="text-muted mb-2"><?= esc_html(substr($product['short_description'] ?? '', 0, 100)) ?>...</p>
                                        <div class="cart-item-price"><?= formatPrice($product['price']) ?></div>
                                        
                                        <?php if ($product['cart_quantity'] > $product['stock']): ?>
                                            <small class="text-warning">
                                                <i class="bi bi-exclamation-triangle me-1"></i>
                                                Only <?= $product['stock'] ?> in stock
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-3 col-12">
                                    <div class="quantity-controls">
                                        <button type="button" class="quantity-btn" onclick="updateQuantity(<?= $product['id'] ?>, -1)">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <input type="number" 
                                               class="quantity-input" 
                                               value="<?= $product['cart_quantity'] ?>" 
                                               min="1" 
                                               max="<?= $product['stock'] ?>"
                                               onchange="setQuantity(<?= $product['id'] ?>, this.value)">
                                        <button type="button" class="quantity-btn" onclick="updateQuantity(<?= $product['id'] ?>, 1)">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <div class="text-center mt-2">
                                        <strong class="line-total-<?= $product['id'] ?>">Subtotal: <?= formatPrice($product['line_total']) ?></strong>
                                    </div>
                                    <div class="text-center mt-2">
                                        <button type="button" 
                                                class="remove-item-btn"
                                                onclick="removeItem(<?= $product['id'] ?>)">
                                            <i class="bi bi-trash me-2"></i>Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Cart Summary -->
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h4 class="summary-title">
                            <i class="bi bi-receipt me-2"></i>Order Summary
                        </h4>
                        
                        <div class="summary-row">
                            <span id="item-count-text">Subtotal (<?= count($products_in_cart) ?> items)</span>
                            <strong id="cart-subtotal"><?= formatPrice($subtotal) ?></strong>
                        </div>
                        
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span class="text-muted">Calculated at checkout</span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="fw-bold">Total</span>
                            <strong id="cart-total"><?= formatPrice($subtotal) ?></strong>
                        </div>

                        <?php if (empty($errors)): ?>
                            <a href="checkout" class="checkout-btn">
                                <i class="bi bi-credit-card me-2"></i>Proceed to Checkout
                            </a>
                        <?php else: ?>
                            <button class="checkout-btn" disabled>
                                <i class="bi bi-exclamation-triangle me-2"></i>Fix Issues to Continue
                            </button>
                        <?php endif; ?>
                        
                        <!-- <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                Secure checkout powered by SSL
                            </small>
                        </div> -->
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Cart Management Scripts -->
<script>
// Global cart data
let cartData = {};

// Initialize cart data from PHP
<?php if (!empty($products_in_cart)): ?>
cartData = {
    <?php foreach ($products_in_cart as $product): ?>
    '<?= $product['id'] ?>': {
        price: <?= $product['price'] ?>,
        quantity: <?= $product['cart_quantity'] ?>,
        stock: <?= $product['stock'] ?>,
        name: '<?= esc_html(addslashes($product['name'])) ?>'
    },
    <?php endforeach; ?>
};
<?php endif; ?>

function updateQuantity(productId, change) {
    const input = document.querySelector(`[data-product-id="${productId}"] .quantity-input`);
    const currentValue = parseInt(input.value);
    const maxValue = parseInt(input.max);
    const newValue = Math.max(1, Math.min(maxValue, currentValue + change));
    
    if (newValue !== currentValue) {
        input.value = newValue;
        setQuantity(productId, newValue);
    }
}

function setQuantity(productId, quantity) {
    quantity = Math.max(1, parseInt(quantity));
    const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
    const input = cartItem.querySelector('.quantity-input');
    
    // Check stock limit
    if (quantity > cartData[productId].stock) {
        quantity = cartData[productId].stock;
        input.value = quantity;
        showNotification('warning', `Only ${cartData[productId].stock} units available for ${cartData[productId].name}`);
    }
    
    // Show loading state
    cartItem.style.opacity = '0.7';
    
    // Send AJAX request
    fetch('update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}&action=update`
    })
    .then(response => response.json())
            .then(data => {
        if (data.success) {
            // Update local cart data
            cartData[productId].quantity = quantity;
            
            // Update UI elements dynamically
            updateCartItemDisplay(productId);
            updateCartSummary();
            updateCartBadge();
            
            // Show success message
            showNotification('success', 'Cart updated successfully');
            
            // Debug log
            console.log('Cart updated:', {productId, quantity, cartData});
        } else {
            showNotification('error', data.message || 'Error updating cart');
        }
        
        // Restore opacity
        cartItem.style.opacity = '1';
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Error updating cart');
        cartItem.style.opacity = '1';
    });
}

function removeItem(productId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
        
        // Show loading state
        cartItem.style.opacity = '0.7';
        
        // Send AJAX request
        fetch('update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&action=remove`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove from cart data
                delete cartData[productId];
                
                // Animate removal
                cartItem.style.transform = 'translateX(-100%)';
                cartItem.style.transition = 'transform 0.3s ease';
                
                setTimeout(() => {
                    cartItem.remove();
                    updateCartSummary();
                    updateCartBadge();
                    
                    // Check if cart is empty
                    if (Object.keys(cartData).length === 0) {
                        location.reload(); // Reload to show empty cart state
                    }
                }, 300);
                
                showNotification('success', 'Item removed from cart');
            } else {
                showNotification('error', data.message || 'Error removing item');
                cartItem.style.opacity = '1';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Error removing item');
            cartItem.style.opacity = '1';
        });
    }
}

function updateCartItemDisplay(productId) {
    const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
    const quantity = cartData[productId].quantity;
    const price = cartData[productId].price;
    const lineTotal = quantity * price;
    
    // Update quantity input
    cartItem.querySelector('.quantity-input').value = quantity;
    
    // Update line total using specific class
    const lineTotalElement = document.querySelector(`.line-total-${productId}`);
    if (lineTotalElement) {
        lineTotalElement.textContent = `Subtotal: ৳${lineTotal.toLocaleString('en-BD', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        console.log(`Updated line total for product ${productId}: ${lineTotal}`);
    } else {
        console.error(`Line total element not found for product ${productId}`);
    }
}

function updateCartSummary() {
    let subtotal = 0;
    let itemCount = 0;
    
    // Calculate new totals
    Object.values(cartData).forEach(item => {
        subtotal += item.price * item.quantity;
        itemCount += item.quantity;
    });
    
    const formattedSubtotal = `৳${subtotal.toLocaleString('en-BD', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    
    // Update subtotal using specific ID
    const subtotalElement = document.getElementById('cart-subtotal');
    if (subtotalElement) {
        subtotalElement.textContent = formattedSubtotal;
        console.log(`Updated subtotal: ${formattedSubtotal}`);
    } else {
        console.error('Subtotal element not found');
    }
    
    // Update total using specific ID
    const totalElement = document.getElementById('cart-total');
    if (totalElement) {
        totalElement.textContent = formattedSubtotal;
        console.log(`Updated total: ${formattedSubtotal}`);
    } else {
        console.error('Total element not found');
    }
    
    // Update item count using specific ID
    const itemCountElement = document.getElementById('item-count-text');
    if (itemCountElement) {
        itemCountElement.textContent = `Subtotal (${Object.keys(cartData).length} items)`;
        console.log(`Updated item count: ${Object.keys(cartData).length} items`);
    } else {
        console.error('Item count element not found');
    }
    
    // Update Cart Items header using specific ID
    const cartItemsHeader = document.getElementById('cart-items-header');
    if (cartItemsHeader) {
        cartItemsHeader.textContent = `Cart Items (${Object.keys(cartData).length})`;
        console.log(`Updated header count: ${Object.keys(cartData).length}`);
    } else {
        console.error('Cart items header not found');
    }
}

function updateCartBadge() {
    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            const badges = document.querySelectorAll('.cart-badge');
            badges.forEach(badge => {
                badge.textContent = data.count;
                badge.style.display = data.count > 0 ? 'inline-block' : 'none';
            });
        })
        .catch(error => console.error('Error updating cart badge:', error));
}

function showNotification(type, message) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.cart-notification');
    existingNotifications.forEach(n => n.remove());
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show cart-notification`;
    notification.style.cssText = 'position: fixed; top: 100px; right: 20px; z-index: 9999; max-width: 300px;';
    
    const icon = type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'x-circle';
    notification.innerHTML = `
        <i class="bi bi-${icon} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
        if (notification && notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// Auto-update quantities on direct input
document.addEventListener('DOMContentLoaded', function() {
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const productId = this.closest('[data-product-id]').dataset.productId;
                setQuantity(productId, this.value);
            }, 800); // Increased delay to reduce server calls
        });
        
        // Also handle blur event for immediate update
        input.addEventListener('blur', function() {
            clearTimeout(timeout);
            const productId = this.closest('[data-product-id]').dataset.productId;
            setQuantity(productId, this.value);
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>

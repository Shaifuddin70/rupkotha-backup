<?php
// Cart Update Handler - AJAX endpoint for cart operations
// Prevent any output before JSON
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'includes/functions.php';

// Clear any output buffer after includes and set headers
ob_clean();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get input data
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$action = in_array($action, ['update', 'remove', 'clear']) ? $action : '';

// Validate required fields
if (!$product_id || !$action) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters. Product ID: ' . ($product_id ?: 'null') . ', Action: ' . ($action ?: 'null')]);
    exit;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

try {
    switch ($action) {
        case 'update':
            if (!$quantity || $quantity < 1) {
                echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
                exit;
            }

            // Verify product exists and get stock
            $stmt = $pdo->prepare("SELECT id, name, stock FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                exit;
            }

            // Check stock availability
            if ($quantity > $product['stock']) {
                echo json_encode([
                    'success' => false, 
                    'message' => "Only {$product['stock']} units of '{$product['name']}' are available"
                ]);
                exit;
            }

            // Update quantity in cart
            $_SESSION['cart'][$product_id] = $quantity;
            
            // Get updated cart totals
            $cart_total = 0;
            $cart_count = 0;
            foreach ($_SESSION['cart'] as $pid => $qty) {
                if ($pid == $product_id) {
                    $cart_total += $product['price'] * $quantity;
                } else {
                    // Get other product prices for total calculation
                    $price_stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
                    $price_stmt->execute([$pid]);
                    $other_price = $price_stmt->fetchColumn();
                    if ($other_price) {
                        $cart_total += $other_price * $qty;
                    }
                }
                $cart_count += $qty;
            }

            echo json_encode([
                'success' => true, 
                'message' => 'Cart updated successfully',
                'new_quantity' => $quantity,
                'line_total' => $product['price'] * $quantity,
                'cart_total' => $cart_total,
                'cart_count' => count($_SESSION['cart'])
            ]);
            break;

        case 'remove':
            // Remove item from cart
            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
                
                // Calculate updated totals
                $cart_total = 0;
                $cart_count = 0;
                if (!empty($_SESSION['cart'])) {
                    $remaining_ids = array_keys($_SESSION['cart']);
                    if (!empty($remaining_ids)) {
                        $placeholders = implode(',', array_fill(0, count($remaining_ids), '?'));
                        $stmt = $pdo->prepare("SELECT id, price FROM products WHERE id IN ($placeholders)");
                        $stmt->execute($remaining_ids);
                        $prices = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                        
                        foreach ($_SESSION['cart'] as $pid => $qty) {
                            if (isset($prices[$pid])) {
                                $cart_total += $prices[$pid] * $qty;
                                $cart_count += $qty;
                            }
                        }
                    }
                }
                
                $response = [
                    'success' => true, 
                    'message' => 'Item removed from cart',
                    'cart_total' => $cart_total,
                    'cart_count' => count($_SESSION['cart'])
                ];
                
                ob_clean();
                echo json_encode($response);
                exit;
            } else {
                $response = [
                    'success' => false, 
                    'message' => 'Item not found in cart'
                ];
                ob_clean();
                echo json_encode($response);
                exit;
            }
            break;

        case 'clear':
            // Clear entire cart
            $_SESSION['cart'] = [];
            echo json_encode([
                'success' => true, 
                'message' => 'Cart cleared'
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (PDOException $e) {
    error_log('Cart Update PDO Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred. Please try again.',
        'error' => (defined('DEBUG') && DEBUG) ? $e->getMessage() : null
    ]);
} catch (Exception $e) {
    error_log('Cart Update Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred. Please try again.',
        'error' => (defined('DEBUG') && DEBUG) ? $e->getMessage() : null
    ]);
}
?>

<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    redirect('modules/security/login.php');
}

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    redirect('modules/shopping-cart/cart.php');
}

$db = Database::getInstance()->getConnection();
$errors = [];
$success = false;

// Get user's default shipping address
$stmt = $db->prepare("SELECT * FROM user_addresses WHERE user_id = ? AND is_default = 1");
$stmt->execute([$_SESSION['user_id']]);
$default_address = $stmt->fetch();

// Calculate cart totals
$cart_items = [];
$subtotal = 0;
$shipping = 10.00; // Fixed shipping rate for demo

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $stmt = $db->prepare("SELECT id, name, stock FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll();
    
    foreach ($products as $product) {
        $cart_item = $_SESSION['cart'][$product['id']];
        $item_subtotal = $cart_item['price'] * $cart_item['quantity'];
        $subtotal += $item_subtotal;
        
        // Verify stock availability
        if ($product['stock'] < $cart_item['quantity']) {
            $errors[] = "Not enough stock available for {$product['name']}";
        }
        
        $cart_items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $cart_item['price'],
            'quantity' => $cart_item['quantity'],
            'subtotal' => $item_subtotal
        ];
    }
}

$total = $subtotal + $shipping;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Create order
        $stmt = $db->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, status, created_at) 
                             VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->execute([
            $_SESSION['user_id'],
            $total,
            $_POST['shipping_address']
        ]);
        
        $order_id = $db->lastInsertId();
        
        // Create order items and update stock
        foreach ($cart_items as $item) {
            $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                 VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $order_id,
                $item['id'],
                $item['quantity'],
                $item['price']
            ]);
            
            // Update product stock
            $stmt = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['id']]);
        }
        
        $db->commit();
        
        // Clear cart after successful order
        unset($_SESSION['cart']);
        
        // Redirect to order confirmation
        redirect("modules/orders/view.php?id=$order_id");
        
    } catch (Exception $e) {
        $db->rollBack();
        $errors[] = "Failed to process order. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="checkout-container">
        <h2>Checkout</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="checkout-grid">
            <div class="order-summary">
                <h3>Order Summary</h3>
                <?php foreach ($cart_items as $item): ?>
                    <div class="summary-item">
                        <span class="item-name">
                            <?php echo $item['name']; ?> x <?php echo $item['quantity']; ?>
                        </span>
                        <span class="item-price">
                            $<?php echo number_format($item['subtotal'], 2); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
                
                <div class="summary-totals">
                    <div class="subtotal">
                        <span>Subtotal:</span>
                        <span>$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="shipping">
                        <span>Shipping:</span>
                        <span>$<?php echo number_format($shipping, 2); ?></span>
                    </div>
                    <div class="total">
                        <span>Total:</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="shipping-details">
                <h3>Shipping Address</h3>
                <form method="POST">
                    <div class="form-group">
                        <textarea name="shipping_address" rows="4" required><?php 
                            echo $default_address ? $default_address['address'] : ''; 
                        ?></textarea>
                    </div>
                    
                    <div class="payment-method">
                        <h3>Payment Method</h3>
                        <div class="form-group">
                            <label>
                                <input type="radio" name="payment_method" value="cod" checked>
                                Cash on Delivery
                            </label>
                        </div>
                    </div>
                    
                    <div class="checkout-actions">
                        <a href="cart.php" class="btn-back">Back to Cart</a>
                        <button type="submit" class="btn-place-order">Place Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 
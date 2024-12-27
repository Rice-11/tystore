<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    redirect('modules/security/login.php');
}

$db = Database::getInstance()->getConnection();
$cart_items = [];
$total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $product_ids = array_keys($_SESSION['cart']);
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    
    $stmt = $db->prepare("SELECT id, name, photo, stock FROM products WHERE id IN ($placeholders)");
    $stmt->execute($product_ids);
    $products = $stmt->fetchAll();
    
    foreach ($products as $product) {
        $cart_item = $_SESSION['cart'][$product['id']];
        $subtotal = $cart_item['price'] * $cart_item['quantity'];
        $total += $subtotal;
        
        $cart_items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'photo' => $product['photo'],
            'price' => $cart_item['price'],
            'quantity' => $cart_item['quantity'],
            'stock' => $product['stock'],
            'subtotal' => $subtotal
        ];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="cart-container">
        <div class="cart-header">
            <h2>Shopping Cart</h2>
            <a href="products.php" class="continue-shopping">Continue Shopping</a>
        </div>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <p>Your cart is empty</p>
                <a href="products.php" class="btn-shop">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item" id="item-<?php echo $item['id']; ?>">
                        <div class="item-image">
                            <?php if ($item['photo']): ?>
                                <img src="../../uploads/products/<?php echo $item['photo']; ?>" 
                                     alt="<?php echo $item['name']; ?>">
                            <?php else: ?>
                                <div class="no-photo">No Photo</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="item-details">
                            <h3><?php echo $item['name']; ?></h3>
                            <p class="item-price">$<?php echo number_format($item['price'], 2); ?> each</p>
                        </div>
                        
                        <div class="item-quantity">
                            <input type="number" 
                                   value="<?php echo $item['quantity']; ?>" 
                                   min="1" 
                                   max="<?php echo $item['stock']; ?>"
                                   onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                        </div>
                        
                        <div class="item-subtotal">
                            $<?php echo number_format($item['subtotal'], 2); ?>
                        </div>
                        
                        <button class="btn-remove" onclick="removeItem(<?php echo $item['id']; ?>)">
                            Remove
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="cart-summary">
                <div class="total">
                    <span>Total:</span>
                    <span class="total-amount">$<?php echo number_format($total, 2); ?></span>
                </div>
                
                <div class="checkout-actions">
                    <a href="products.php" class="btn-continue">Continue Shopping</a>
                    <a href="checkout.php" class="btn-checkout">Proceed to Checkout</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function updateQuantity(productId, quantity) {
        $.post('update_cart.php', {
            product_id: productId,
            quantity: quantity
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.message || 'Failed to update quantity');
                location.reload();
            }
        }, 'json');
    }

    function removeItem(productId) {
        if (confirm('Are you sure you want to remove this item?')) {
            $.post('remove_from_cart.php', {
                product_id: productId
            }, function(response) {
                if (response.success) {
                    $('#item-' + productId).fadeOut(300, function() {
                        $(this).remove();
                        location.reload();
                    });
                } else {
                    alert('Failed to remove item');
                }
            }, 'json');
        }
    }
    </script>
</body>
</html> 
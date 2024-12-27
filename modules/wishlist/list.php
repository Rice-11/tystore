<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    redirect('modules/security/login.php');
}

$db = Database::getInstance()->getConnection();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get total items for pagination
$stmt = $db->prepare("SELECT COUNT(*) as total FROM wishlists WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Get wishlist items with pagination
$stmt = $db->prepare("SELECT p.*, w.created_at as added_date 
                      FROM wishlists w 
                      JOIN products p ON w.product_id = p.id 
                      WHERE w.user_id = ? 
                      ORDER BY w.created_at DESC 
                      LIMIT ? OFFSET ?");
$stmt->execute([$_SESSION['user_id'], $per_page, $offset]);
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Wishlist</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="wishlist-container">
        <div class="wishlist-header">
            <h2>My Wishlist</h2>
        </div>
        
        <?php if (empty($items)): ?>
            <div class="no-items">
                <p>Your wishlist is empty</p>
                <a href="../../index.php" class="btn-shop">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="wishlist-grid">
                <?php foreach ($items as $item): ?>
                    <div class="wishlist-item" id="item-<?php echo $item['id']; ?>">
                        <div class="item-image">
                            <img src="../../uploads/products/<?php echo $item['photo']; ?>" alt="<?php echo $item['name']; ?>">
                        </div>
                        <div class="item-details">
                            <h3><?php echo $item['name']; ?></h3>
                            <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                            <div class="item-stock">
                                <?php if ($item['stock'] > 0): ?>
                                    <span class="in-stock">In Stock</span>
                                <?php else: ?>
                                    <span class="out-of-stock">Out of Stock</span>
                                <?php endif; ?>
                            </div>
                            <div class="item-actions">
                                <?php if ($item['stock'] > 0): ?>
                                    <button onclick="addToCart(<?php echo $item['id']; ?>)" class="btn-add-cart">
                                        Add to Cart
                                    </button>
                                <?php endif; ?>
                                <button onclick="removeFromWishlist(<?php echo $item['id']; ?>)" class="btn-remove">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" 
                       class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
    function removeFromWishlist(productId) {
        if (confirm('Are you sure you want to remove this item from your wishlist?')) {
            $.post('remove.php', {
                product_id: productId
            }, function(response) {
                if (response.success) {
                    $('#item-' + productId).fadeOut(300, function() {
                        $(this).remove();
                        if ($('.wishlist-item').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert(response.message || 'Failed to remove item');
                }
            }, 'json');
        }
    }

    function addToCart(productId) {
        $.post('../shopping-cart/add_to_cart.php', {
            product_id: productId,
            quantity: 1
        }, function(response) {
            if (response.success) {
                alert('Product added to cart');
            } else {
                alert(response.message || 'Failed to add to cart');
            }
        }, 'json');
    }
    </script>
</body>
</html> 
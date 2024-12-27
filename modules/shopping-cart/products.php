<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    redirect('modules/security/login.php');
}

$db = Database::getInstance()->getConnection();
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get categories for filter
$stmt = $db->query("SELECT id, name FROM categories WHERE status = 'active'");
$categories = $stmt->fetchAll();

// Build query conditions
$conditions = ["p.stock > 0"];
$params = [];

if ($search) {
    $conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $conditions[] = "p.category_id = ?";
    $params[] = $category;
}

$whereClause = "WHERE " . implode(" AND ", $conditions);

// Get total products for pagination
$countQuery = "SELECT COUNT(*) as total FROM products p $whereClause";
$stmt = $db->prepare($countQuery);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Get products with search, filter and pagination
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          $whereClause 
          ORDER BY p.name ASC 
          LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $db->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shop Products</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="shop-container">
        <div class="shop-header">
            <h2>Products</h2>
            <a href="cart.php" class="cart-link">
                View Cart (<span id="cart-count">0</span>)
            </a>
        </div>
        
        <div class="filters">
            <form method="GET" class="search-form">
                <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Search products...">
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                                <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo $cat['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Filter</button>
            </form>
        </div>
        
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <?php if ($product['photo']): ?>
                        <img src="../../uploads/products/<?php echo $product['photo']; ?>" 
                             alt="<?php echo $product['name']; ?>" class="product-image">
                    <?php else: ?>
                        <div class="no-photo">No Photo</div>
                    <?php endif; ?>
                    
                    <div class="product-info">
                        <h3><?php echo $product['name']; ?></h3>
                        <p class="category"><?php echo $product['category_name']; ?></p>
                        <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                        <p class="stock">In Stock: <?php echo $product['stock']; ?></p>
                        
                        <div class="product-actions">
                            <input type="number" min="1" max="<?php echo $product['stock']; ?>" 
                                   value="1" class="quantity-input" id="qty-<?php echo $product['id']; ?>">
                            <button onclick="addToCart(<?php echo $product['id']; ?>)" class="btn-add-cart">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>&category=<?php echo $category; ?>" 
                   class="<?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function updateCartCount() {
        $.get('get_cart_count.php', function(count) {
            $('#cart-count').text(count);
        });
    }

    function addToCart(productId) {
        var quantity = $('#qty-' + productId).val();
        
        $.post('add_to_cart.php', {
            product_id: productId,
            quantity: quantity
        }, function(response) {
            if (response.success) {
                alert('Product added to cart!');
                updateCartCount();
            } else {
                alert(response.message || 'Failed to add product to cart');
            }
        }, 'json');
    }

    // Update cart count when page loads
    $(document).ready(function() {
        updateCartCount();
    });
    </script>
</body>
</html> 
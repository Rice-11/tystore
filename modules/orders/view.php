<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    redirect('modules/security/login.php');
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = Database::getInstance()->getConnection();

// Get order details
$stmt = $db->prepare("SELECT o.*, u.username 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      WHERE o.id = ? AND (o.user_id = ? OR ? = 1)");
$stmt->execute([$order_id, $_SESSION['user_id'], isAdmin()]);
$order = $stmt->fetch();

if (!$order) {
    redirect('modules/orders/list.php');
}

// Get order items
$stmt = $db->prepare("SELECT oi.*, p.name, p.photo 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

// Get order status history
$stmt = $db->prepare("SELECT * FROM order_status_history 
                      WHERE order_id = ? 
                      ORDER BY created_at DESC");
$stmt->execute([$order_id]);
$status_history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Details #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="order-container">
        <div class="order-header">
            <h2>Order #<?php echo $order_id; ?></h2>
            <div class="order-status <?php echo strtolower($order['status']); ?>">
                <?php echo ucfirst($order['status']); ?>
            </div>
        </div>
        
        <div class="order-grid">
            <div class="order-info">
                <div class="info-section">
                    <h3>Order Information</h3>
                    <p><strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                    <p><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>
                    <p><strong>Total Amount:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
                </div>
                
                <div class="info-section">
                    <h3>Shipping Address</h3>
                    <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                </div>
                
                <?php if (isAdmin()): ?>
                <div class="info-section">
                    <h3>Customer Information</h3>
                    <p><strong>Username:</strong> <?php echo $order['username']; ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="order-items">
                <h3>Order Items</h3>
                <?php foreach ($items as $item): ?>
                    <div class="order-item">
                        <div class="item-image">
                            <?php if ($item['photo']): ?>
                                <img src="../../uploads/products/<?php echo $item['photo']; ?>" 
                                     alt="<?php echo $item['name']; ?>">
                            <?php else: ?>
                                <div class="no-photo">No Photo</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="item-details">
                            <h4><?php echo $item['name']; ?></h4>
                            <p class="item-price">$<?php echo number_format($item['price'], 2); ?> x <?php echo $item['quantity']; ?></p>
                            <p class="item-subtotal">
                                Subtotal: $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <?php if (!empty($status_history)): ?>
        <div class="status-history">
            <h3>Order Status History</h3>
            <div class="timeline">
                <?php foreach ($status_history as $status): ?>
                    <div class="timeline-item">
                        <div class="timeline-status">
                            <?php echo ucfirst($status['status']); ?>
                        </div>
                        <div class="timeline-date">
                            <?php echo date('M j, Y g:i A', strtotime($status['created_at'])); ?>
                        </div>
                        <?php if ($status['notes']): ?>
                            <div class="timeline-notes">
                                <?php echo $status['notes']; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isAdmin()): ?>
        <div class="admin-actions">
            <h3>Update Order Status</h3>
            <form method="POST" action="update_status.php">
                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                <div class="form-group">
                    <select name="status" required>
                        <option value="">Select Status</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <textarea name="notes" placeholder="Add notes (optional)"></textarea>
                </div>
                <button type="submit" class="btn-update">Update Status</button>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="order-actions">
            <a href="list.php" class="btn-back">Back to Orders</a>
            <?php if ($order['status'] === 'pending'): ?>
                <a href="cancel.php?id=<?php echo $order_id; ?>" 
                   class="btn-cancel"
                   onclick="return confirm('Are you sure you want to cancel this order?')">
                    Cancel Order
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 
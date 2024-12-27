<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    redirect('modules/security/login.php');
}

$db = Database::getInstance()->getConnection();
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query conditions
$conditions = [];
$params = [];

if (!isAdmin()) {
    $conditions[] = "o.user_id = ?";
    $params[] = $_SESSION['user_id'];
}

if ($status) {
    $conditions[] = "o.status = ?";
    $params[] = $status;
}

$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get total orders for pagination
$countQuery = "SELECT COUNT(*) as total FROM orders o $whereClause";
$stmt = $db->prepare($countQuery);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Get orders with pagination
$query = "SELECT o.*, u.username 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          $whereClause 
          ORDER BY o.created_at DESC 
          LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Orders</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="orders-container">
        <div class="orders-header">
            <h2>Orders</h2>
            <div class="filter-options">
                <a href="?status=" class="<?php echo $status === '' ? 'active' : ''; ?>">All</a>
                <a href="?status=pending" class="<?php echo $status === 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?status=processing" class="<?php echo $status === 'processing' ? 'active' : ''; ?>">Processing</a>
                <a href="?status=shipped" class="<?php echo $status === 'shipped' ? 'active' : ''; ?>">Shipped</a>
                <a href="?status=delivered" class="<?php echo $status === 'delivered' ? 'active' : ''; ?>">Delivered</a>
                <a href="?status=cancelled" class="<?php echo $status === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
            </div>
        </div>
        
        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <p>No orders found</p>
            </div>
        <?php else: ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <?php if (isAdmin()): ?>
                            <th>Customer</th>
                        <?php endif; ?>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <?php if (isAdmin()): ?>
                                <td><?php echo $order['username']; ?></td>
                            <?php endif; ?>
                            <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo strtolower($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td>
                                <a href="view.php?id=<?php echo $order['id']; ?>" class="btn-view">View</a>
                                <?php if ($order['status'] === 'pending'): ?>
                                    <a href="cancel.php?id=<?php echo $order['id']; ?>" 
                                       class="btn-cancel"
                                       onclick="return confirm('Are you sure you want to cancel this order?')">
                                        Cancel
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>" 
                       class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html> 
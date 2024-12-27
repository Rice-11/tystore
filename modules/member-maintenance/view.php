<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('modules/security/login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = Database::getInstance()->getConnection();

// Get member details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'member'");
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    redirect('modules/member-maintenance/list.php');
}

// Get member's orders
$stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$id]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Member Details</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="member-detail-container">
        <h2>Member Details</h2>
        
        <div class="member-info">
            <?php if ($member['profile_photo']): ?>
                <img src="../../uploads/profiles/<?php echo $member['profile_photo']; ?>" 
                     alt="Profile" class="detail-photo">
            <?php endif; ?>
            
            <div class="info-group">
                <label>Username:</label>
                <span><?php echo $member['username']; ?></span>
            </div>
            
            <div class="info-group">
                <label>Email:</label>
                <span><?php echo $member['email']; ?></span>
            </div>
            
            <div class="info-group">
                <label>Join Date:</label>
                <span><?php echo date('Y-m-d', strtotime($member['created_at'])); ?></span>
            </div>
        </div>
        
        <h3>Order History</h3>
        <?php if (!empty($orders)): ?>
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                        <td><?php echo ucfirst($order['status']); ?></td>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No orders found for this member.</p>
        <?php endif; ?>
        
        <div class="actions">
            <a href="list.php" class="btn-back">Back to List</a>
            <a href="delete.php?id=<?php echo $member['id']; ?>" 
               class="btn-delete" 
               onclick="return confirm('Are you sure you want to delete this member?')">Delete Member</a>
        </div>
    </div>
</body>
</html> 
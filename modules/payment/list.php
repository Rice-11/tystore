<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
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

if ($status) {
    $conditions[] = "p.status = ?";
    $params[] = $status;
}

$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get total payments for pagination
$countQuery = "SELECT COUNT(*) as total FROM payments p $whereClause";
$stmt = $db->prepare($countQuery);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Get payments with pagination
$query = "SELECT p.*, o.id as order_id, u.username 
          FROM payments p 
          JOIN orders o ON p.order_id = o.id 
          JOIN users u ON o.user_id = u.id 
          $whereClause 
          ORDER BY p.created_at DESC 
          LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $db->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Management</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="payment-list-container">
        <div class="payment-header">
            <h2>Payment Management</h2>
            <div class="filter-section">
                <form method="GET">
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="verified" <?php echo $status === 'verified' ? 'selected' : ''; ?>>Verified</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </form>
            </div>
        </div>
        
        <?php if (empty($payments)): ?>
            <div class="no-results">
                <p>No payments found</p>
            </div>
        <?php else: ?>
            <table class="payment-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td>#<?php echo $payment['order_id']; ?></td>
                            <td><?php echo $payment['username']; ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                            <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                            <td>
                                <span class="status-badge <?php echo $payment['status']; ?>">
                                    <?php echo ucfirst($payment['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($payment['created_at'])); ?></td>
                            <td>
                                <button onclick="viewDetails(<?php echo $payment['id']; ?>)" 
                                        class="btn-view">View Details</button>
                                <?php if ($payment['status'] === 'pending'): ?>
                                    <button onclick="verifyPayment(<?php echo $payment['id']; ?>, 'verified')" 
                                            class="btn-verify">Verify</button>
                                    <button onclick="verifyPayment(<?php echo $payment['id']; ?>, 'rejected')" 
                                            class="btn-reject">Reject</button>
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

    <!-- Payment Details Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="paymentDetails"></div>
        </div>
    </div>

    <script>
    function viewDetails(paymentId) {
        $.get('get_details.php', { payment_id: paymentId }, function(response) {
            if (response.success) {
                $('#paymentDetails').html(response.html);
                $('#paymentModal').show();
            } else {
                alert('Failed to load payment details');
            }
        }, 'json');
    }

    function verifyPayment(paymentId, status) {
        if (confirm('Are you sure you want to ' + status + ' this payment?')) {
            $.post('verify.php', {
                payment_id: paymentId,
                status: status
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message || 'Failed to update payment status');
                }
            }, 'json');
        }
    }

    // Close modal when clicking the close button or outside the modal
    $('.close, .modal').click(function(e) {
        if (e.target === this) {
            $('#paymentModal').hide();
        }
    });
    </script>
</body>
</html> 
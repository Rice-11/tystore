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
    $conditions[] = "r.status = ?";
    $params[] = $status;
}

$whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// Get total reviews for pagination
$countQuery = "SELECT COUNT(*) as total FROM reviews r $whereClause";
$stmt = $db->prepare($countQuery);
$stmt->execute($params);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Get reviews with pagination
$query = "SELECT r.*, p.name as product_name, u.username 
          FROM reviews r 
          JOIN products p ON r.product_id = p.id 
          JOIN users u ON r.user_id = u.id 
          $whereClause 
          ORDER BY r.created_at DESC 
          LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $db->prepare($query);
$stmt->execute($params);
$reviews = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Review Management</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="reviews-container">
        <div class="reviews-header">
            <h2>Review Management</h2>
            <div class="filter-options">
                <a href="?status=" class="<?php echo $status === '' ? 'active' : ''; ?>">All</a>
                <a href="?status=pending" class="<?php echo $status === 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?status=approved" class="<?php echo $status === 'approved' ? 'active' : ''; ?>">Approved</a>
                <a href="?status=rejected" class="<?php echo $status === 'rejected' ? 'active' : ''; ?>">Rejected</a>
            </div>
        </div>
        
        <?php if (empty($reviews)): ?>
            <div class="no-results">
                <p>No reviews found</p>
            </div>
        <?php else: ?>
            <table class="reviews-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td><?php echo $review['product_name']; ?></td>
                            <td><?php echo $review['username']; ?></td>
                            <td>
                                <div class="star-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td><?php echo nl2br(htmlspecialchars($review['comment'])); ?></td>
                            <td>
                                <span class="status-badge <?php echo $review['status']; ?>">
                                    <?php echo ucfirst($review['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d', strtotime($review['created_at'])); ?></td>
                            <td>
                                <?php if ($review['status'] === 'pending'): ?>
                                    <button onclick="updateStatus(<?php echo $review['id']; ?>, 'approved')" 
                                            class="btn-approve">
                                        Approve
                                    </button>
                                    <button onclick="updateStatus(<?php echo $review['id']; ?>, 'rejected')" 
                                            class="btn-reject">
                                        Reject
                                    </button>
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

    <script>
    function updateStatus(reviewId, status) {
        if (confirm('Are you sure you want to ' + status + ' this review?')) {
            $.post('update_status.php', {
                review_id: reviewId,
                status: status
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message || 'Failed to update review status');
                }
            }, 'json');
        }
    }
    </script>
</body>
</html> 
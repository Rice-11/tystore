<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('modules/security/login.php');
}

$db = Database::getInstance()->getConnection();
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total members for pagination
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'member' AND (username LIKE ? OR email LIKE ?)");
$searchParam = "%$search%";
$stmt->execute([$searchParam, $searchParam]);
$total = $stmt->fetch()['total'];
$total_pages = ceil($total / $per_page);

// Get members with search and pagination
$stmt = $db->prepare("SELECT id, username, email, profile_photo, created_at 
                     FROM users 
                     WHERE role = 'member' AND (username LIKE ? OR email LIKE ?)
                     ORDER BY created_at DESC
                     LIMIT ? OFFSET ?");
$stmt->execute([$searchParam, $searchParam, $per_page, $offset]);
$members = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Member Management</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="member-container">
        <h2>Member Management</h2>
        
        <div class="search-box">
            <form method="GET">
                <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Search members...">
                <button type="submit">Search</button>
            </form>
        </div>
        
        <table class="member-table">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $member): ?>
                <tr>
                    <td>
                        <?php if ($member['profile_photo']): ?>
                            <img src="../../uploads/profiles/<?php echo $member['profile_photo']; ?>" 
                                 alt="Profile" class="member-photo">
                        <?php else: ?>
                            <div class="no-photo">No Photo</div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $member['username']; ?></td>
                    <td><?php echo $member['email']; ?></td>
                    <td><?php echo date('Y-m-d', strtotime($member['created_at'])); ?></td>
                    <td>
                        <a href="view.php?id=<?php echo $member['id']; ?>" class="btn-view">View</a>
                        <a href="delete.php?id=<?php echo $member['id']; ?>" 
                           class="btn-delete" 
                           onclick="return confirm('Are you sure you want to delete this member?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo $search; ?>" 
                   class="<?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html> 
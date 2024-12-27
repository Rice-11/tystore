<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('modules/security/login.php');
}

$db = Database::getInstance()->getConnection();

// Function to build category tree
function buildCategoryTree($categories, $parentId = null) {
    $tree = [];
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parentId) {
            $children = buildCategoryTree($categories, $category['id']);
            if ($children) {
                $category['children'] = $children;
            }
            $tree[] = $category;
        }
    }
    return $tree;
}

// Get all categories
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$categoryTree = buildCategoryTree($categories);

// Function to display category tree
function displayCategoryTree($categories, $level = 0) {
    foreach ($categories as $category) {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
        $status_class = $category['status'] === 'active' ? 'status-active' : 'status-inactive';
        ?>
        <tr>
            <td>
                <?php echo $indent; ?>
                <?php if ($level > 0): ?>
                    └─
                <?php endif; ?>
                <?php echo htmlspecialchars($category['name']); ?>
            </td>
            <td><?php echo htmlspecialchars($category['description']); ?></td>
            <td>
                <span class="status-badge <?php echo $status_class; ?>">
                    <?php echo ucfirst($category['status']); ?>
                </span>
            </td>
            <td>
                <a href="edit.php?id=<?php echo $category['id']; ?>" class="btn-edit">Edit</a>
                <?php if (!isset($category['has_products']) || !$category['has_products']): ?>
                    <a href="delete.php?id=<?php echo $category['id']; ?>" 
                       class="btn-delete"
                       onclick="return confirm('Are you sure you want to delete this category?')">
                        Delete
                    </a>
                <?php endif; ?>
            </td>
        </tr>
        <?php
        if (isset($category['children'])) {
            displayCategoryTree($category['children'], $level + 1);
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Category Management</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="category-container">
        <div class="category-header">
            <h2>Category Management</h2>
            <a href="create.php" class="btn-add">Add New Category</a>
        </div>
        
        <table class="category-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php displayCategoryTree($categoryTree); ?>
            </tbody>
        </table>
    </div>
</body>
</html> 
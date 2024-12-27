<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('modules/security/login.php');
}

$db = Database::getInstance()->getConnection();
$errors = [];

// Get all categories for parent selection
$categories = $db->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $status = sanitize($_POST['status']);
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = "Category name is required";
    }
    
    // Check if category name already exists
    $stmt = $db->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        $errors[] = "A category with this name already exists";
    }
    
    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO categories (name, description, status, parent_id) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$name, $description, $status, $parent_id ?: null])) {
            $_SESSION['success'] = "Category created successfully";
            redirect('modules/category-maintenance/list.php');
        } else {
            $errors[] = "Failed to create category";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Category</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="form-container">
        <h2>Create New Category</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Parent Category:</label>
                <select name="parent_id">
                    <option value="">None (Top Level)</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo $category['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" rows="4"></textarea>
            </div>
            
            <div class="form-group">
                <label>Status:</label>
                <select name="status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="form-buttons">
                <button type="submit">Create Category</button>
                <a href="list.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html> 
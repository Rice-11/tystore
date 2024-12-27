<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('modules/security/login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = Database::getInstance()->getConnection();
$errors = [];

// Get category details
$stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    redirect('modules/category-maintenance/list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $status = sanitize($_POST['status']);
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = "Category name is required";
    }
    
    // Check if category name already exists (excluding current category)
    $stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
    $stmt->execute([$name, $id]);
    if ($stmt->fetch()) {
        $errors[] = "A category with this name already exists";
    }
    
    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE categories SET name = ?, description = ?, status = ? WHERE id = ?");
        
        if ($stmt->execute([$name, $description, $status, $id])) {
            $_SESSION['success'] = "Category updated successfully";
            redirect('modules/category-maintenance/list.php');
        } else {
            $errors[] = "Failed to update category";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Category</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="form-container">
        <h2>Edit Category</h2>
        
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
                <input type="text" name="name" value="<?php echo $category['name']; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" rows="4"><?php echo $category['description']; ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Status:</label>
                <select name="status" required>
                    <option value="active" <?php echo $category['status'] === 'active' ? 'selected' : ''; ?>>
                        Active
                    </option>
                    <option value="inactive" <?php echo $category['status'] === 'inactive' ? 'selected' : ''; ?>>
                        Inactive
                    </option>
                </select>
            </div>
            
            <div class="form-buttons">
                <button type="submit">Update Category</button>
                <a href="list.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html> 
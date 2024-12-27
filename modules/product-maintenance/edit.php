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
$success = false;

// Get product details
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    redirect('modules/product-maintenance/list.php');
}

// Get categories for dropdown
$stmt = $db->query("SELECT id, name FROM categories WHERE status = 'active'");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $category_id = (int)$_POST['category_id'];
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = "Product name is required";
    }
    if ($price <= 0) {
        $errors[] = "Price must be greater than zero";
    }
    if ($stock < 0) {
        $errors[] = "Stock cannot be negative";
    }
    
    if (empty($errors)) {
        // Handle photo upload if new photo is provided
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $filename = $_FILES['photo']['name'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($filetype, $allowed)) {
                $photo = uniqid() . "." . $filetype;
                $uploadPath = UPLOAD_PATH . "/products/" . $photo;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                    // Delete old photo if exists
                    if ($product['photo']) {
                        $oldPath = UPLOAD_PATH . "/products/" . $product['photo'];
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    
                    // Update product with new photo
                    $stmt = $db->prepare("UPDATE products SET name = ?, description = ?, price = ?, 
                                        stock = ?, category_id = ?, photo = ? WHERE id = ?");
                    $success = $stmt->execute([$name, $description, $price, $stock, $category_id, $photo, $id]);
                } else {
                    $errors[] = "Failed to upload photo";
                }
            } else {
                $errors[] = "Invalid photo format. Allowed: jpg, jpeg, png";
            }
        } else {
            // Update product without changing photo
            $stmt = $db->prepare("UPDATE products SET name = ?, description = ?, price = ?, 
                                stock = ?, category_id = ? WHERE id = ?");
            $success = $stmt->execute([$name, $description, $price, $stock, $category_id, $id]);
        }
        
        if ($success) {
            redirect('modules/product-maintenance/list.php');
        } else {
            $errors[] = "Failed to update product";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="form-container">
        <h2>Edit Product</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" value="<?php echo $product['name']; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Category:</label>
                <select name="category_id" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                            <?php echo $category['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" rows="4"><?php echo $product['description']; ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Price:</label>
                <input type="number" name="price" step="0.01" min="0" 
                       value="<?php echo $product['price']; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Stock:</label>
                <input type="number" name="stock" min="0" 
                       value="<?php echo $product['stock']; ?>" required>
            </div>
            
            <?php if ($product['photo']): ?>
                <div class="current-photo">
                    <img src="../../uploads/products/<?php echo $product['photo']; ?>" 
                         alt="Current Photo" class="product-preview">
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label>New Photo (leave empty to keep current):</label>
                <input type="file" name="photo" accept="image/*">
            </div>
            
            <div class="form-buttons">
                <button type="submit">Update Product</button>
                <a href="list.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html> 
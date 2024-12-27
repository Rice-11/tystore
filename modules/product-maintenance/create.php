<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('modules/security/login.php');
}

$db = Database::getInstance()->getConnection();
$errors = [];

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
        $photo = null;
        
        // Handle photo upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $filename = $_FILES['photo']['name'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($filetype, $allowed)) {
                $photo = uniqid() . "." . $filetype;
                $uploadPath = UPLOAD_PATH . "/products/" . $photo;
                
                if (!move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                    $errors[] = "Failed to upload photo";
                }
            } else {
                $errors[] = "Invalid photo format. Allowed: jpg, jpeg, png";
            }
        }
        
        if (empty($errors)) {
            $stmt = $db->prepare("INSERT INTO products (name, description, price, stock, category_id, photo) 
                                VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $description, $price, $stock, $category_id, $photo])) {
                redirect('modules/product-maintenance/list.php');
            } else {
                $errors[] = "Failed to create product";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Product</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="form-container">
        <h2>Create New Product</h2>
        
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
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Category:</label>
                <select name="category_id" required>
                    <option value="">Select Category</option>
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
                <label>Price:</label>
                <input type="number" name="price" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label>Stock:</label>
                <input type="number" name="stock" min="0" required>
            </div>
            
            <div class="form-group">
                <label>Photo:</label>
                <input type="file" name="photo" accept="image/*">
            </div>
            
            <div class="form-buttons">
                <button type="submit">Create Product</button>
                <a href="list.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html> 
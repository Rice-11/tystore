<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('modules/security/login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = Database::getInstance()->getConnection();

// Start transaction
$db->beginTransaction();

try {
    // Get product photo before deletion
    $stmt = $db->prepare("SELECT photo FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    // Delete product reviews
    $stmt = $db->prepare("DELETE FROM reviews WHERE product_id = ?");
    $stmt->execute([$id]);
    
    // Delete from wishlist
    $stmt = $db->prepare("DELETE FROM wishlist WHERE product_id = ?");
    $stmt->execute([$id]);
    
    // Delete from order items
    $stmt = $db->prepare("DELETE FROM order_items WHERE product_id = ?");
    $stmt->execute([$id]);
    
    // Delete the product
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    
    // Delete product photo if exists
    if ($product && $product['photo']) {
        $photoPath = UPLOAD_PATH . "/products/" . $product['photo'];
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }
    }
    
    $db->commit();
    $_SESSION['success'] = "Product deleted successfully";
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = "Failed to delete product";
}

redirect('modules/product-maintenance/list.php'); 
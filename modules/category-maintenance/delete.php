<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('modules/security/login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = Database::getInstance()->getConnection();

// Check if category exists
$stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    $_SESSION['error'] = "Category not found";
    redirect('modules/category-maintenance/list.php');
}

// Check if category has products
$stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
$stmt->execute([$id]);
$product_count = $stmt->fetch()['count'];

if ($product_count > 0) {
    $_SESSION['error'] = "Cannot delete category with associated products";
    redirect('modules/category-maintenance/list.php');
}

// Delete category
$stmt = $db->prepare("DELETE FROM categories WHERE id = ?");

if ($stmt->execute([$id])) {
    $_SESSION['success'] = "Category deleted successfully";
} else {
    $_SESSION['error'] = "Failed to delete category";
}

redirect('modules/category-maintenance/list.php'); 
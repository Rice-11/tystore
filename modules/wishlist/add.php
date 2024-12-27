<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

$db = Database::getInstance()->getConnection();

// Check if product exists
$stmt = $db->prepare("SELECT id FROM products WHERE id = ?");
$stmt->execute([$product_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Check if already in wishlist
$stmt = $db->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
$stmt->execute([$_SESSION['user_id'], $product_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Product already in wishlist']);
    exit;
}

// Add to wishlist
$stmt = $db->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)");

if ($stmt->execute([$_SESSION['user_id'], $product_id])) {
    echo json_encode(['success' => true, 'message' => 'Product added to wishlist']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add product to wishlist']);
} 
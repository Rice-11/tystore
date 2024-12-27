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

// Remove from wishlist
$stmt = $db->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");

if ($stmt->execute([$_SESSION['user_id'], $product_id])) {
    echo json_encode(['success' => true, 'message' => 'Product removed from wishlist']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove product from wishlist']);
} 
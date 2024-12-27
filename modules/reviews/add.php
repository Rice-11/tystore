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
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = isset($_POST['comment']) ? sanitize($_POST['comment']) : '';

if ($product_id <= 0 || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or rating']);
    exit;
}

$db = Database::getInstance()->getConnection();

// Check if user has already reviewed this product
$stmt = $db->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
$stmt->execute([$product_id, $_SESSION['user_id']]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
    exit;
}

// Check if user has purchased this product
$stmt = $db->prepare("SELECT o.id 
                      FROM orders o 
                      JOIN order_items oi ON o.id = oi.order_id 
                      WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered'");
$stmt->execute([$_SESSION['user_id'], $product_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You can only review products you have purchased']);
    exit;
}

// Add review
$stmt = $db->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");

if ($stmt->execute([$product_id, $_SESSION['user_id'], $rating, $comment])) {
    // Update product average rating
    $stmt = $db->prepare("UPDATE products p 
                         SET rating = (
                             SELECT AVG(rating) 
                             FROM reviews 
                             WHERE product_id = ? AND status = 'approved'
                         ) 
                         WHERE id = ?");
    $stmt->execute([$product_id, $product_id]);
    
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
} 
<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
$status = isset($_POST['status']) ? sanitize($_POST['status']) : '';

if ($review_id <= 0 || !in_array($status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid review or status']);
    exit;
}

$db = Database::getInstance()->getConnection();

// Start transaction
$db->beginTransaction();

try {
    // Update review status
    $stmt = $db->prepare("UPDATE reviews SET status = ? WHERE id = ?");
    $stmt->execute([$status, $review_id]);
    
    // If approved, update product average rating
    if ($status === 'approved') {
        $stmt = $db->prepare("SELECT product_id FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        $product_id = $stmt->fetch()['product_id'];
        
        $stmt = $db->prepare("UPDATE products p 
                             SET rating = (
                                 SELECT AVG(rating) 
                                 FROM reviews 
                                 WHERE product_id = ? AND status = 'approved'
                             ) 
                             WHERE id = ?");
        $stmt->execute([$product_id, $product_id]);
    }
    
    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Failed to update review status']);
} 
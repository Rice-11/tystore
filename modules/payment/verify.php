<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('modules/security/login.php');
}

$payment_id = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
$status = isset($_POST['status']) ? sanitize($_POST['status']) : '';

if ($payment_id <= 0 || !in_array($status, ['verified', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$db = Database::getInstance()->getConnection();

// Start transaction
$db->beginTransaction();

try {
    // Update payment status
    $stmt = $db->prepare("UPDATE payments SET status = ? WHERE id = ?");
    $stmt->execute([$status, $payment_id]);
    
    // Get order ID
    $stmt = $db->prepare("SELECT order_id FROM payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $order_id = $stmt->fetch()['order_id'];
    
    // Update order status based on payment verification
    $order_status = $status === 'verified' ? 'processing' : 'payment_failed';
    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$order_status, $order_id]);
    
    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Failed to update payment status']);
} 
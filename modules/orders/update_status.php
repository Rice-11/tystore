<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/mailer.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('modules/security/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = isset($_POST['status']) ? sanitize($_POST['status']) : '';
$notes = isset($_POST['notes']) ? sanitize($_POST['notes']) : '';

if ($order_id <= 0 || empty($status)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    // Get order details including user email
    $stmt = $db->prepare("SELECT o.*, u.email FROM orders o 
                         JOIN users u ON o.user_id = u.id 
                         WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Update order status
    $stmt = $db->prepare("UPDATE orders SET status = ?, notes = ? WHERE id = ?");
    $stmt->execute([$status, $notes, $order_id]);

    // Send email notification
    $mailer = new Mailer();
    $mailer->sendOrderStatusUpdate($order_id, $status, $order['email']);

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $db->rollBack();
    error_log("Order status update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
} 
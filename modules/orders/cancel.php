<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/mailer.php';

if (!isLoggedIn()) {
    redirect('modules/security/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    $db->beginTransaction();

    // Check if order belongs to user and can be cancelled
    $stmt = $db->prepare("SELECT o.*, u.email FROM orders o 
                         JOIN users u ON o.user_id = u.id 
                         WHERE o.id = ? AND o.user_id = ? 
                         AND o.status IN ('pending', 'pending_verification')");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception('Order not found or cannot be cancelled');
    }

    // Update order status
    $stmt = $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$order_id]);

    // Return items to stock
    $stmt = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        $stmt = $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $stmt->execute([$item['quantity'], $item['product_id']]);
    }

    // Send email notification
    $mailer = new Mailer();
    $mailer->sendOrderStatusUpdate($order_id, 'cancelled', $order['email']);

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $db->rollBack();
    error_log("Order cancellation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to cancel order']);
} 
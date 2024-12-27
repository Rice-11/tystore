<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;

if ($payment_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment ID']);
    exit;
}

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT p.*, o.id as order_id, u.username 
                      FROM payments p 
                      JOIN orders o ON p.order_id = o.id 
                      JOIN users u ON o.user_id = u.id 
                      WHERE p.id = ?");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch();

if (!$payment) {
    echo json_encode(['success' => false, 'message' => 'Payment not found']);
    exit;
}

$html = "
<div class='payment-details'>
    <h3>Payment Details</h3>
    <div class='detail-row'>
        <label>Order ID:</label>
        <span>#" . $payment['order_id'] . "</span>
    </div>
    <div class='detail-row'>
        <label>Customer:</label>
        <span>" . htmlspecialchars($payment['username']) . "</span>
    </div>
    <div class='detail-row'>
        <label>Payment Method:</label>
        <span>" . ucfirst(str_replace('_', ' ', $payment['payment_method'])) . "</span>
    </div>
    <div class='detail-row'>
        <label>Amount:</label>
        <span>$" . number_format($payment['amount'], 2) . "</span>
    </div>
    <div class='detail-row'>
        <label>Payment Details:</label>
        <span>" . nl2br(htmlspecialchars($payment['payment_details'])) . "</span>
    </div>
    <div class='detail-row'>
        <label>Date:</label>
        <span>" . date('Y-m-d H:i', strtotime($payment['created_at'])) . "</span>
    </div>
    <div class='detail-row'>
        <label>Status:</label>
        <span class='status-badge " . $payment['status'] . "'>" . ucfirst($payment['status']) . "</span>
    </div>
    " . ($payment['receipt_photo'] ? "
    <div class='receipt-image'>
        <h4>Receipt</h4>
        <img src='../../uploads/receipts/" . $payment['receipt_photo'] . "' alt='Receipt'>
    </div>
    " : "") . "
</div>";

echo json_encode(['success' => true, 'html' => $html]); 
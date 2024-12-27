<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/mailer.php';

if (!isLoggedIn()) {
    redirect('modules/security/login.php');
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$db = Database::getInstance()->getConnection();
$errors = [];

// Check if order exists and belongs to user
$stmt = $db->prepare("SELECT o.*, u.email 
                      FROM orders o 
                      JOIN users u ON o.user_id = u.id 
                      WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect('modules/orders/history.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = sanitize($_POST['payment_method']);
    $payment_details = sanitize($_POST['payment_details']);
    
    // Handle receipt upload
    $receipt_photo = null;
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $receipt_photo = uploadFile($_FILES['receipt'], UPLOAD_PATH_RECEIPTS);
        if (!$receipt_photo) {
            $errors[] = "Failed to upload receipt. Please ensure it's an image file under 5MB.";
        }
    } else {
        $errors[] = "Please upload a receipt";
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Insert payment record
            $stmt = $db->prepare("INSERT INTO payments (order_id, payment_method, amount, payment_details, receipt_photo) 
                                VALUES (?, ?, ?, ?, ?)");
            
            if (!$stmt->execute([$order_id, $payment_method, $order['total_amount'], $payment_details, $receipt_photo])) {
                throw new Exception("Failed to save payment details");
            }

            // Update order status
            $stmt = $db->prepare("UPDATE orders SET status = 'pending_verification' WHERE id = ?");
            if (!$stmt->execute([$order_id])) {
                throw new Exception("Failed to update order status");
            }

            // Send email notification
            $mailer = new Mailer();
            $mailer->sendPaymentConfirmation($order_id, $order['total_amount'], $order['email']);

            $db->commit();
            $_SESSION['success'] = "Payment submitted successfully";
            redirect('modules/orders/history.php');

        } catch (Exception $e) {
            $db->rollBack();
            error_log("Payment processing error: " . $e->getMessage());
            $errors[] = "Failed to process payment. Please try again.";
            
            // Delete uploaded file if exists
            if ($receipt_photo && file_exists(UPLOAD_PATH_RECEIPTS . $receipt_photo)) {
                unlink(UPLOAD_PATH_RECEIPTS . $receipt_photo);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Process Payment</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="payment-container">
        <h2>Process Payment</h2>
        <div class="order-summary">
            <h3>Order Summary</h3>
            <p>Order ID: #<?php echo $order['id']; ?></p>
            <p>Total Amount: $<?php echo number_format($order['total_amount'], 2); ?></p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Payment Method:</label>
                <select name="payment_method" required>
                    <option value="credit_card">Credit Card</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="e_wallet">E-Wallet</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Payment Details:</label>
                <textarea name="payment_details" rows="4" required 
                          placeholder="Enter payment details (e.g., transaction ID, last 4 digits of card)"></textarea>
            </div>
            
            <div class="form-group">
                <label>Upload Receipt:</label>
                <input type="file" name="receipt" accept="image/*" required>
                <small>Accepted formats: JPG, PNG, GIF (Max size: 5MB)</small>
            </div>
            
            <div class="form-buttons">
                <button type="submit">Submit Payment</button>
                <a href="../orders/history.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html> 
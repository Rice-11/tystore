<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('modules/security/login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$db = Database::getInstance()->getConnection();

// Start transaction
$db->beginTransaction();

try {
    // Delete member's orders
    $stmt = $db->prepare("DELETE FROM orders WHERE user_id = ?");
    $stmt->execute([$id]);
    
    // Delete member's reviews
    $stmt = $db->prepare("DELETE FROM reviews WHERE user_id = ?");
    $stmt->execute([$id]);
    
    // Delete member's wishlist
    $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ?");
    $stmt->execute([$id]);
    
    // Finally, delete the member
    $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'member'");
    $stmt->execute([$id]);
    
    $db->commit();
    
    $_SESSION['success'] = "Member deleted successfully";
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = "Failed to delete member";
}

redirect('modules/member-maintenance/list.php'); 
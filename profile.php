<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['valid'])) {
    header("Location: login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($result);

// Handle form submissions
if (isset($_POST['update_profile'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    $update_query = "UPDATE users SET username = ?, email = ? WHERE id = ?";
    $stmt = mysqli_prepare($con, $update_query);
    mysqli_stmt_bind_param($stmt, "ssi", $username, $email, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['username'] = $username;
        $_SESSION['valid'] = $email;
        $success_message = "Profile updated successfully!";
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($current_password === $user_data['password'] && $new_password === $confirm_password) {
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($con, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $new_password, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $password_message = "Password changed successfully!";
        }
    } else {
        $password_error = "Invalid current password or passwords don't match!";
    }
}

// Handle account deletion
if (isset($_POST['delete_account'])) {
    if ($_POST['confirm_delete'] === 'DELETE') {
        $delete_query = "DELETE FROM users WHERE id = ?";
        $stmt = mysqli_prepare($con, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            session_destroy();
            header("Location: register.php");
            exit();
        }
    }
}

// Get purchase history
$purchase_query = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = mysqli_prepare($con, $purchase_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$purchases = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - Tystore</title>
    <link rel="stylesheet" href="css/alogin.css">
    <style>
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .settings-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 1.2em;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        
        .purchase-history {
            margin-top: 20px;
        }
        
        .purchase-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .success-message {
            color: green;
            margin: 10px 0;
        }
        
        .error-message {
            color: red;
            margin: 10px 0;
        }
        
        .delete-account {
            color: #ff3333;
            margin-top: 30px;
        }
        
        .points-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include("header.php"); ?>
    <div class="settings-container">
        <h1>Profile Settings</h1>
        
        <!-- Profile Information -->
        <div class="settings-section">
            <h2 class="section-title">Profile Information</h2>
            <?php if(isset($success_message)): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <form action="" method="post">
                <div class="field input">
                    <label for="username">Username</label>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                </div>
                
                <div class="field input">
                    <label for="email">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                </div>
                
                <div class="field">
                    <input type="submit" name="update_profile" value="Update Profile" class="btn">
                </div>
            </form>
        </div>
        
        <!-- Change Password -->
        <div class="settings-section">
            <h2 class="section-title">Change Password</h2>
            <?php if(isset($password_message)): ?>
                <div class="success-message"><?php echo $password_message; ?></div>
            <?php endif; ?>
            <?php if(isset($password_error)): ?>
                <div class="error-message"><?php echo $password_error; ?></div>
            <?php endif; ?>
            
            <form action="" method="post">
                <div class="field input">
                    <label for="current_password">Current Password</label>
                    <input type="password" name="current_password" required>
                </div>
                
                <div class="field input">
                    <label for="new_password">New Password</label>
                    <input type="password" name="new_password" required>
                </div>
                
                <div class="field input">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <div class="field">
                    <input type="submit" name="change_password" value="Change Password" class="btn">
                </div>
            </form>
        </div>
        
        <!-- Membership Points -->
        <div class="settings-section">
            <h2 class="section-title">Membership Points</h2>
            <div class="points-section">
                <h3>Current Points: <?php echo isset($user_data['points']) ? $user_data['points'] : 0; ?></h3>
                <p>Membership Level: <?php 
                    $points = isset($user_data['points']) ? $user_data['points'] : 0;
                    if($points >= 1000) echo "Gold";
                    else if($points >= 500) echo "Silver";
                    else echo "Bronze";
                ?></p>
            </div>
        </div>
        
        <!-- Purchase History -->
        <div class="settings-section">
            <h2 class="section-title">Purchase History</h2>
            <div class="purchase-history">
                <?php if(mysqli_num_rows($purchases) > 0): ?>
                    <?php while($purchase = mysqli_fetch_assoc($purchases)): ?>
                        <div class="purchase-item">
                            <p>Order ID: <?php echo htmlspecialchars($purchase['order_id']); ?></p>
                            <p>Date: <?php echo htmlspecialchars($purchase['order_date']); ?></p>
                            <p>Total: $<?php echo htmlspecialchars($purchase['total_amount']); ?></p>
                            <p>Status: <?php echo htmlspecialchars($purchase['status']); ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No purchase history available.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Delete Account -->
        <div class="settings-section delete-account">
            <h2 class="section-title">Delete Account</h2>
            <p>Warning: This action cannot be undone.</p>
            <form action="" method="post" onsubmit="return confirm('Are you sure you want to delete your account? This cannot be undone.');">
                <div class="field input">
                    <label for="confirm_delete">Type "DELETE" to confirm</label>
                    <input type="text" name="confirm_delete" required>
                </div>
                
                <div class="field">
                    <input type="submit" name="delete_account" value="Delete Account" class="btn" style="background-color: #ff3333;">
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php
session_start();
require_once '../config.php';

$mode = isset($_GET['mode']) ? $_GET['mode'] : '2fa';
$error_message = '';

if(isset($_POST['verify'])) {
    $entered_code = $_POST['code'];
    
    if($mode === 'reset') {
        // Password reset verification
        if($entered_code == $_SESSION['reset_code']) {
            if(time() - $_SESSION['reset_time'] <= 300) { // 5 minutes
                // Show password reset form
                ?>
                <div class="reset-password-form">
                    <header>Reset Password</header>
                    <form action="" method="post">
                        <div class="field input">
                            <label>New Password</label>
                            <input type="password" name="new_password" required>
                        </div>
                        <div class="field input">
                            <label>Confirm Password</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        <div class="field">
                            <input type="submit" name="reset_password" value="Reset Password">
                        </div>
                    </form>
                </div>
                <?php
                exit();
            } else {
                $error_message = "Code has expired";
            }
        } else {
            $error_message = "Invalid code";
        }
    } else {
        // Normal 2FA verification
        if($entered_code == $_SESSION['2fa_code']) {
            if(time() - $_SESSION['2fa_time'] <= 300) {
                $role = $_SESSION['role'];
                header("Location: " . ($role === 'admin' ? '../admin_dashboard.php' : '../index.php'));
                exit();
            } else {
                $error_message = "Code has expired";
            }
        } else {
            $error_message = "Invalid code";
        }
    }
}

// Handle password reset submission
if(isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($new_password === $confirm_password) {
        $email = $_SESSION['reset_email'];
        mysqli_query($con, "UPDATE users SET password='$new_password' WHERE email='$email'");
        
        // Clear reset session variables
        unset($_SESSION['reset_code']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_time']);
        
        header("Location: login.php?message=Password updated successfully");
        exit();
    } else {
        $error_message = "Passwords do not match";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification</title>
    <link rel="stylesheet" href="../css/alogin.css">
</head>
<body>
    <div class="container">
        <div class="box form-box">
            <header><?php echo ($mode === 'reset' ? 'Reset Password' : '2FA Verification'); ?></header>
            <form action="" method="post">
                <div class="field input">
                    <label>Enter Verification Code</label>
                    <input type="text" name="code" required>
                </div>

                <?php if($error_message): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="field">
                    <input type="submit" class="btn" name="verify" value="Verify">
                </div>
            </form>
        </div>
    </div>
</body>
</html>
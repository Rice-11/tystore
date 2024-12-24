<?php
session_start();

// Check if user is logged in and 2FA code exists
if(!isset($_SESSION['2fa_code']) || !isset($_SESSION['valid'])) {
    header("Location: login.php");
    exit();
}

// Check if code has expired (5 minutes)
if(time() - $_SESSION['2fa_time'] > 300) {
    session_destroy();
    header("Location: login.php?error=expired");
    exit();
}

if(isset($_POST['verify'])) {
    $entered_code = $_POST['code'];
    
    if($entered_code == $_SESSION['2fa_code']) {
        // Code is correct, clean up 2FA session variables
        unset($_SESSION['2fa_code']);
        unset($_SESSION['2fa_time']);
        
        // Redirect based on role
        if($_SESSION['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        $error = "Invalid code. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="alogin.css">
    <title>Verify 2FA</title>
</head>
<body>
    <div class="container">
        <div class="box form-box">
            <header>2FA Verification</header>
            <?php if(isset($error)): ?>
                <div class="message">
                    <p><?php echo $error; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['error']) && $_GET['error'] === 'expired'): ?>
                <div class="message">
                    <p>Verification code has expired. Please login again.</p>
                </div>
                <a href="login.php" class="btn">Back to Login</a>
            <?php else: ?>
                <form action="" method="post">
                    <div class="field input">
                        <label for="code">Enter 6-digit code sent to your email</label>
                        <input type="text" name="code" id="code" required 
                               pattern="[0-9]{6}" maxlength="6" 
                               placeholder="Enter 6-digit code">
                    </div>
                    <div class="field">
                        <input type="submit" class="btn" name="verify" value="Verify">
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
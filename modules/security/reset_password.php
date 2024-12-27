<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        // Generate reset token
        $token = generateToken();
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
        $stmt->execute([$token, $expiry, $email]);
        
        // In a real application, send email with reset link
        // For demo, just show success message
        $success = true;
    } else {
        $errors[] = "Email not found";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="reset-container">
        <h2>Reset Password</h2>
        
        <?php if ($success): ?>
            <div class="success">
                <p>If an account exists with this email, you will receive password reset instructions.</p>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                
                <button type="submit">Reset Password</button>
                <p><a href="login.php">Back to Login</a></p>
            </form>
        <?php endif; ?>
    </div>
</body>
</html> 
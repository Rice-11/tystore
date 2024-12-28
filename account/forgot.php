<?php
session_start();
require_once '../config.php';
use PHPMailer\PHPMailer\PHPMailer;
require '../vendor/autoload.php';

$error_message = '';

if(isset($_POST['submit'])) {
    $email = $_POST['email'];
    
    // See if email exists in database
    $result = mysqli_query($con, "SELECT * FROM users WHERE email='$email'");
    if(mysqli_num_rows($result) > 0) {
        $reset_code = rand(100000, 999999);
        $_SESSION['reset_code'] = $reset_code;
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_time'] = time();
        
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'asigmtest@gmail.com';
            $mail->Password = 'ppkn bijb qykr lgel';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            
            $mail->setFrom('asigmtest@gmail.com', 'Toy Store');
            $mail->addAddress($email);
            
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Code';
            $mail->Body = "Your password reset code is: <b>$reset_code</b><br>This code will expire in 5 minutes.";
            
            $mail->send();
            header("Location: verify_reset.php");
            exit();
        } catch (Exception $e) {
            $error_message = "Error sending reset code";
        }
    } else {
        $error_message = "Email not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/alogin.css">
    <title>Forgot Password</title>
</head>
<body>
    <div class="container">
        <div class="box form-box">
            <header>Forgot Password</header>
            <form action="" method="post">
                <div class="field input">
                    <label>Enter Your Email</label>
                    <input type="email" name="email" required>
                </div>

                <?php if($error_message): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="field">
                    <input type="submit" class="btn" name="submit" value="Send Reset Code">
                </div>
                <div class="links">
                    <a href="login.php">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>



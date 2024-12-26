<?php 
    session_start();
    
    // Import PHPMailer classes
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    // Include PHPMailer autoload
    require 'vendor/autoload.php';
    
    // Include database connection
    require_once 'config.php';
    
    // Initialize variables
    $error_message = '';
    $entered_email = '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/alogin.css">
    <title>Login Page</title>
    <style>
        .error-message {
            color: #ff3333;
            margin: 8px 0;
            font-size: 14px;
        }
        .password-toggle {
            position: relative;
        }
        .password-toggle button {
            position: absolute;
            right: 12px;
            bottom: 22px;
            top: 50%;
            border: none;
            background: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="box form-box">
        <?php 
             if(isset($_POST['submit'])){
                $email = $_POST['email'];
                $password = $_POST['password'];
                
                // Store email for form retention
                $entered_email = htmlspecialchars($email);

                $result = mysqli_query($con,"SELECT * FROM users WHERE email='$email' AND password='$password' ") or die("Select Error");
                $row = mysqli_fetch_assoc($result);

                if(is_array($row) && !empty($row)){
                    $_SESSION['valid'] = $row['email'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['age'] = $row['age'];
                    $_SESSION['id'] = $row['id'];
                    $_SESSION['role'] = $row['role'];
                    
                    // Check if 2FA is needed
                    $needs2FA = false;
                    
                    if($row['role'] === 'admin') {
                        $needs2FA = true;
                    } 
                    else if($row['has_2fa'] == 1) {
                        $needs2FA = true;
                    }
                    
                    if($needs2FA) {
                        // Generate 2FA code
                        $code = rand(100000, 999999);
                        $_SESSION['2fa_code'] = $code;
                        $_SESSION['2fa_time'] = time();
                        
                        $mail = new PHPMailer(true);
                        
                        try {
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'loltanehud92@gmail.com'; // Your Gmail
                            $mail->Password = 'ejsn ssoy ryuj sfcu'; // Your app password
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                            $mail->Port = 465;
                            
                            $mail->setFrom('loltanehud92@gmail.com', 'Tystore');
                            $mail->addAddress($email);
                            
                            $mail->isHTML(true);
                            $mail->Subject = '2FA Verification Code';
                            $mail->Body = "Your verification code is: <b>$code</b><br>This code will expire in 5 minutes.";
                            
                            $mail->send();
                            header("Location: fa.php");
                            exit();
                        } catch (Exception $e) {
                            $error_message = "Error sending verification code: {$mail->ErrorInfo}";
                        }
                    } else {
                        // No 2FA needed
                        header("Location: " . ($row['role'] === 'admin' ? 'admin_dashboard.php' : 'index.php'));
                        exit();
                    }
                } else {
                    $error_message = "Wrong Username or Password";
                }
             }
        ?>
            <header>Login</header>
            <form action="" method="post">
                <div class="field input">
                    <label for="email">Email</label>
                    <input type="text" name="email" id="email" value="<?php echo $entered_email; ?>" required>
                </div>

                <div class="field input password-toggle">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                    <button type="button" id="togglePassword" onclick="togglePasswordVisibility()">
                        👁️
                    </button>
                </div>

                <?php if($error_message): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="field">
                    <input type="submit" class="btn" name="submit" value="Login" required>
                </div>
                <div class="links">
                    Don't have account? <a href="register.php">Sign Up Now</a>
                </div>
            </form>

            <script>
                function togglePasswordVisibility() {
                    const passwordInput = document.getElementById('password');
                    const toggleButton = document.getElementById('togglePassword');
                    
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        toggleButton.textContent = '🔒';
                    } else {
                        passwordInput.type = 'password';
                        toggleButton.textContent = '👁️';
                    }
                }
            </script>
        </div>
    </div>
    <a href="index.php">Back home baby</a>
</body>
</html>
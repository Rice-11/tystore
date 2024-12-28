<?php 
    session_start();
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    require '../vendor/autoload.php';
    require_once '../config.php';
    
    $error_message = '';
    $entered_email = '';

    // Function to send email
    function sendVerificationEmail($email, $code, $subject) {
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
            $mail->Subject = $subject;
            $mail->Body = "Your verification code is: <b>$code</b><br>This code will expire in 5 minutes.";
            
            return $mail->send();
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Handle normal login
    if(isset($_POST['submit'])){
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        $entered_email = htmlspecialchars($email);

        $result = mysqli_query($con,"SELECT * FROM users WHERE email='$email' AND password='$password' ") or die("Select Error");
        $row = mysqli_fetch_assoc($result);

        if(is_array($row) && !empty($row)){
            $_SESSION['valid'] = $row['email'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['age'] = $row['age'];
            $_SESSION['id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            
            $needs2FA = false;
            
            if($row['role'] === 'admin') {
                $needs2FA = true;
            } 
            else if($row['has_2fa'] == 1) {
                $needs2FA = true;
            }
            
            if($needs2FA) {
                $code = rand(100000, 999999);
                $_SESSION['2fa_code'] = $code;
                $_SESSION['2fa_time'] = time();
                
                if(sendVerificationEmail($email, $code, '2FA Verification Code')) {
                    header("Location: fa.php");
                    exit();
                } else {
                    $error_message = "Error sending verification code";
                }
            } else {
                header("Location: " . ($row['role'] === 'admin' ? '../admin_dashboard.php' : '../index.php'));
                exit();
            }
        } else {
            $error_message = "Wrong Username or Password";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/alogin.css">
    <link rel="stylesheet" href="../css/profile.css">
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
                    <br>
                    <a href="forgot.php">Forgot Password?</a>
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
    <a href="../index.php">Back home baby</a>
</body>
</html>
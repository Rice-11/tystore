<?php
session_start();
require_once '../config.php';

$error_message = '';

if(!isset($_SESSION['reset_code']) || !isset($_SESSION['reset_email'])) {
    header("Location: login.php");
    exit();
}

if(isset($_POST['submit'])) {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($new_password === $confirm_password) {
        $email = $_SESSION['reset_email'];
        mysqli_query($con, "UPDATE users SET password='$new_password' WHERE email='$email'");
        
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
    <link rel="stylesheet" href="../css/alogin.css">
    <title>Reset Password</title>

    <style>
        .password-requirements {
            margin-top: 5px;
            font-size: 0.9em;
        }
        .requirement {
            color: #667;
            display: block; 
        }
        .requirement.hidden {
            display: none; 
        }
        .error-message {
            color: #ff3333;
            margin-top: 5px;
            font-size: 0.9em;
        }
        .password-toggle {
            position: relative;
        }
        .password-toggle button {
            position: absolute;
            right: 12px;
            bottom: 50px;
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
            <header>Reset Password</header>
            <form action="" method="post" id="passwordresetForm" onsubmit="return validateForm()">
            <div class="field input password-toggle">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" autocomplete="off" required>
                    <button type="button" id="togglePassword" onclick="togglePasswordVisibility()">
                        👁️
                    </button>
                    <div class="password-requirements">
                        <div id="length" class="requirement">8 characters</div>
                        <div id="uppercase" class="requirement">At least one uppercase letter</div>
                        <div id="number" class="requirement">At least one number</div>
                        <div id="special" class="requirement">At least one special character</div>
                    </div>
                </div>

                <div class="field input">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <?php if($error_message): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="field">
                    <input type="submit" class="btn" name="submit" value="Register" id="submitBtn" required>
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

                document.getElementById('password').addEventListener('input', function() {
                    const password = this.value;
                    
                    // When the requiermetn is met the comment will dis a pear
                    document.getElementById('length').className = 
                        'requirement ' + (password.length >= 8 ? 'hidden' : '');
                    document.getElementById('uppercase').className = 
                        'requirement ' + (/[A-Z]/.test(password) ? 'hidden' : '');
                    document.getElementById('number').className = 
                        'requirement ' + (/[0-9]/.test(password) ? 'hidden' : '');
                    document.getElementById('special').className = 
                        'requirement ' + (/[^A-Za-z0-9]/.test(password) ? 'hidden' : '');
                });

                function validateForm() {
                    const password = document.getElementById('password').value;
                    const email = document.getElementById('email').value;
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    // Check password 
                    if (password.length < 8 || 
                        !/[A-Z]/.test(password) || 
                        !/[0-9]/.test(password) || 
                        !/[^A-Za-z0-9]/.test(password)) {
                        alert('Please meet all password requirements');
                        return false;
                    }
                    return true;
                }
            </script>
            
        </div>
    </div>
</body>
</html>
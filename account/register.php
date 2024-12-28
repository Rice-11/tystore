<?php
include("../config.php");

if(isset($_POST["submit"])){
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $age = filter_input(INPUT_POST, 'age', FILTER_SANITIZE_NUMBER_INT);
    $password = $_POST['password'];
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='message'><p>Invalid email format!</p></div>";
        exit();
    }

    // Check if email already exists in database
    $verify_query = mysqli_query($con, "SELECT email FROM users WHERE email='$email'");
    
    if(mysqli_num_rows($verify_query) != 0) {
        echo "<div class='message'><p>This email is already registered!</p></div>";
    } else {
        // Insert new user into database if no dupe
        $insert_query = "INSERT INTO users (username, email, age, password, has_2fa, role) VALUES (?, ?, ?, ?, 0, 'user')";
        $stmt = mysqli_prepare($con, $insert_query);
        mysqli_stmt_bind_param($stmt, "ssis", $username, $email, $age, $password);
        
        if(mysqli_stmt_execute($stmt)) {
            echo "<div class='message' style='color: green;'><p>Registration successful!</p></div>";
            // Redirect to login page after 2 seconds
            echo "<script>
                    setTimeout(function() {
                        window.location.href = '../account/login.php';
                    }, 2000);
                  </script>";
        } else {
            echo "<div class='message'><p>Error: " . mysqli_error($con) . "</p></div>";
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/alogin.css">
    <title>Register</title>
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
            bottom: 80px;
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
            <header>Register</header>
            <form action="" method="post" id="registerForm" onsubmit="return validateForm()">
                <div class="field input">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" autocomplete="off" required>
                </div>

                <div class="field input">
                    <label for="email">Email</label>
                    <input type="text" name="email" id="email" autocomplete="off" required>
                    <div class="error-message" id="emailMessage"></div>
                </div>

                <div class="field input">
                    <label for="age">Age</label>
                    <input type="number" name="age" id="age" autocomplete="off" required>
                </div>

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

                <div class="field">
                    <input type="submit" class="btn" name="submit" value="Register" id="submitBtn" required>
                </div>
                <div class="links">
                    Already a member? <a href="../account/login.php">Sign In</a>
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
                    
                    // Check each requirement and hide if met
                    document.getElementById('length').className = 
                        'requirement ' + (password.length >= 8 ? 'hidden' : '');
                    document.getElementById('uppercase').className = 
                        'requirement ' + (/[A-Z]/.test(password) ? 'hidden' : '');
                    document.getElementById('number').className = 
                        'requirement ' + (/[0-9]/.test(password) ? 'hidden' : '');
                    document.getElementById('special').className = 
                        'requirement ' + (/[^A-Za-z0-9]/.test(password) ? 'hidden' : '');
                });

                document.getElementById('email').addEventListener('input', function() {
                    const email = this.value;
                    const messageDiv = document.getElementById('emailMessage');
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    if (!emailRegex.test(email)) {
                        messageDiv.textContent = 'Please enter a valid email address';
                        messageDiv.style.color = '#ff3333';
                    } else {
                        messageDiv.textContent = '';
                    }
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

                    // Check email format
                    if (!emailRegex.test(email)) {
                        alert('Please enter a valid email address');
                        return false;
                    }

                    return true;
                }
            </script>
        </div>
    </div>
</body>
</html>
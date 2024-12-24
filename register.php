<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="alogin.css">
    <title>Register</title>
    <style>
        .password-strength {
            margin-top: 5px;
            font-size: 0.9em;
        }
        .weak { color: red; }
        .medium { color: orange; }
        .strong { color: green; }
    </style>
</head>
<body>
    <div class="container">
        <div class="box form-box">
            <header>Register</header>
            <?php
                include("config.php");
                if(isset($_POST["submit"])){
                    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
                    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
                    $age = filter_input(INPUT_POST, 'age', FILTER_SANITIZE_NUMBER_INT);
                    $password = $_POST['password'];

                    // Email validation
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        echo "<div class='message'><p>Invalid email format!</p></div><br>";
                        echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button>";
                        exit();
                    }

                    // Password strength check
                    if (strlen($password) < 8) {
                        echo "<div class='message'><p>Password must be at least 8 characters long!</p></div><br>";
                        echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button>";
                        exit();
                    }

                    if (!preg_match("/[A-Z]/", $password)) {
                        echo "<div class='message'><p>Password must contain at least one uppercase letter!</p></div><br>";
                        echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button>";
                        exit();
                    }

                    if (!preg_match("/[0-9]/", $password)) {
                        echo "<div class='message'><p>Password must contain at least one number!</p></div><br>";
                        echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button>";
                        exit();
                    }

                    $verify_query = mysqli_query($con,"SELECT email FROM users WHERE email='$email'");

                    if(mysqli_num_rows($verify_query) !=0 ){
                        echo "<div class='message'>
                                  <p>This email is already registered!</p>
                              </div> <br>";
                        echo "<a href='javascript:self.history.back()'><button class='btn'>Go Back</button>";
                    }
                    else{
                        mysqli_query($con,"INSERT INTO users(username, email, age, password, has_2fa, role) VALUES('$username','$email','$age','$password', 0, 'user')") or die("Error inserting data");
            
                        echo "<div class='message'>
                                  <p>Registration successful!</p>
                              </div> <br>";
                        echo "<a href='login.php'><button class='btn'>Login Now</button>";
                    }  
                } else {
            ?>

            <form action="" method="post" id="registerForm">
                <div class="field input">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" autocomplete="off" required>
                </div>

                <div class="field input">
                    <label for="email">Email</label>
                    <input type="text" name="email" id="email" autocomplete="off" required>
                    <div id="emailMessage"></div>
                </div>

                <div class="field input">
                    <label for="age">Age</label>
                    <input type="number" name="age" id="age" autocomplete="off" required>
                </div>

                <div class="field input">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" autocomplete="off" required>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>

                <div class="field">
                    <input type="submit" class="btn" name="submit" value="Register" required>
                </div>
                <div class="links">
                    Already a member? <a href="login.php">Sign In</a>
                </div>
            </form>

            <script>
                // Real-time password strength checker
                document.getElementById('password').addEventListener('input', function() {
                    const password = this.value;
                    const strengthDiv = document.getElementById('passwordStrength');
                    let strength = 0;
                    let message = '';

                    if (password.length >= 8) strength++;
                    if (password.match(/[A-Z]/)) strength++;
                    if (password.match(/[0-9]/)) strength++;
                    if (password.match(/[^A-Za-z0-9]/)) strength++;

                    switch(strength) {
                        case 0:
                        case 1:
                            message = '<span class="weak">Weak password</span>';
                            break;
                        case 2:
                        case 3:
                            message = '<span class="medium">Medium password</span>';
                            break;
                        case 4:
                            message = '<span class="strong">Strong password</span>';
                            break;
                    }

                    strengthDiv.innerHTML = message;
                });

                // Real-time email format checker
                document.getElementById('email').addEventListener('input', function() {
                    const email = this.value;
                    const messageDiv = document.getElementById('emailMessage');
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    if (!emailRegex.test(email)) {
                        messageDiv.innerHTML = '<span class="weak">Invalid email format</span>';
                    } else {
                        messageDiv.innerHTML = '<span class="strong">Valid email format</span>';
                    }
                });
            </script>
        </div>
        <?php } ?>
    </div>
</body>
</html>
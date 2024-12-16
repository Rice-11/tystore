<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/login.css">
    <title>Register</title>
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

                        $verify_query = mysqli_query($con,"SELECT email FROM users WHERE email='$email'");

                        if(mysqli_num_rows($verify_query) !=0 ){
                            echo "<div class='message'>
                                      <p>This Shit is already in the database man you tweaking</p>
                                  </div> <br>";
                            echo "<a href='javascript:self.history.back()'><button class='btn'>Take me home</button>";
                         }
                         else{
                            mysqli_query($con,"INSERT INTO users(username,email,age,password) VALUES('$username','$email','$age','$password')") or die("Oopsies");
                
                            echo "<div class='message'>
                                      <p>Registration successfully!</p>
                                  </div> <br>";
                            echo "<a href='login.php'><button class='btn'>Login Now</button>";
                         }  
                    }else{
            ?>

            <form action="" method="post">
                <div class="field input">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" autocomplete="off" required>
                </div>

                <div class="field input">
                    <label for="email">Email</label>
                    <input type="text" name="email" id="email" autocomplete="off" required>
                </div>

                <div class="field input">
                    <label for="age">Age</label>
                    <input type="number" name="age" id="age" autocomplete="off" required>
                </div>
                <div class="field input">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" autocomplete="off" required>
                </div>

                <div class="field">
                    
                    <input type="submit" class="btn" name="submit" value="Register" required>
                </div>
                <div class="links">
                    Already a member? <a href="login.php">Sign In</a>
                </div>
            </form>
        </div>
        <?php } ?>
      </div>
</body>
</html>
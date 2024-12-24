<?php 
    session_start();
    
    // Import PHPMailer classes
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;

    // Include PHPMailer autoload
    require 'vendor/autoload.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="alogin.css">
    <title>Login Page</title>
</head>
<body>
    <div class="container">
        <div class="box form-box">
        <?php 
             include("config.php");
             if(isset($_POST['submit'])){
               $email = $_POST['email'];
               $password = $_POST['password'];

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
                   
                   // Admins must use 2FA
                   if($row['role'] === 'admin') {
                       $needs2FA = true;
                   } 
                   // For other users, check if they've enabled 2FA
                   else if($row['has_2fa'] == 1) {
                       $needs2FA = true;
                   }
                   
                   if($needs2FA) {
                       // Generate 2FA code
                       $code = rand(100000, 999999);
                       $_SESSION['2fa_code'] = $code;
                       $_SESSION['2fa_time'] = time();
                       
                       // Create new PHPMailer instance
                       $mail = new PHPMailer(true);
                       
                       try {
                           // Server settings
                           $mail->isSMTP();
                           $mail->Host = 'smtp.gmail.com';
                           $mail->SMTPAuth = true;
                           $mail->Username = 'loltanehud92@gmail.com'; // Replace with your Gmail
                           $mail->Password = 'ejsn ssoy ryuj sfcu'; // Replace with your app password
                           $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                           $mail->Port = 465;
                           
                           // Recipients
                           $mail->setFrom('loltanehud92@gmail.com', 'Tystore');
                           $mail->addAddress($email);
                           
                           // Content
                           $mail->isHTML(true);
                           $mail->Subject = '2FA Verification Code';
                           $mail->Body = "Your verification code is: <b>$code</b><br>This code will expire in 5 minutes.";
                           
                           $mail->send();
                           header("Location: fa.php");
                           exit();
                       } catch (Exception $e) {
                           echo "<div class='message'>
                                 <p>Error sending verification code: {$mail->ErrorInfo}</p>
                                 </div> <br>";
                           echo "<a href='login.php'><button class='btn'>Go Back</button>";
                       }
                   } else {
                       // No 2FA needed
                       header("Location: " . ($row['role'] === 'admin' ? 'admin_dashboard.php' : 'index.php'));
                       exit();
                   }
               } else {
                   echo "<div class='message'>
                     <p>Wrong Username or Password</p>
                      </div> <br>";
                  echo "<a href='login.php'><button class='btn'>Go Back</button>";
               }
             } else {
        ?>
            <header>Login</header>
            <form action="" method="post">
                <div class="field input">
                    <label for="email">Email</label>
                    <input type="text" name="email" id="email" required>
                </div>

                <div class="field input">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required>
                </div>

                <div class="field">
                    <input type="submit" class="btn" name="submit" value="Login" required>
                </div>
                <div class="links">
                    Don't have account? <a href="register.php">Sign Up Now</a>
                </div>
            </form>
        <?php } ?>
        </div>
    </div>
</body>
</html>
<?php
session_start();
require_once '../config.php';

$error_message = '';

if(isset($_POST['verify'])) {
    $entered_code = $_POST['code'];
    
    if($entered_code == $_SESSION['reset_code']) {
        if(time() - $_SESSION['reset_time'] <= 300) { // 5 minutes
            header("Location: ../account/reset_password.php");
            exit();
        } else {
            $error_message = "Code has expired";
        }
    } else {
        $error_message = "Invalid code";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/alogin.css">
    <title>Verify Reset Code</title>
</head>
<body>
    <div class="container">
        <div class="box form-box">
            <header>Verify Reset Code</header>
            <form action="" method="post">
                <div class="field input">
                    <label>Enter Reset Code</label>
                    <input type="text" name="code" required>
                </div>

                <?php if($error_message): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="field">
                    <input type="submit" class="btn" name="verify" value="Verify Code">
                </div>
            </form>
        </div>
    </div>
</body>
</html>

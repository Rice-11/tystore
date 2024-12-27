<?php
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isLoggedIn()) {
    redirect('modules/security/login.php');
}

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Fetch current user data
$stmt = $db->prepare("SELECT username, email, profile_photo FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    
    // Handle profile photo upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['profile_photo']['name'];
        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($filetype, $allowed)) {
            $newFilename = uniqid() . "." . $filetype;
            $uploadPath = UPLOAD_PATH . "/profiles/" . $newFilename;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $uploadPath)) {
                $stmt = $db->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                $stmt->execute([$newFilename, $userId]);
                $user['profile_photo'] = $newFilename;
            }
        }
    }
    
    // Update profile information
    $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    if ($stmt->execute([$username, $email, $userId])) {
        $_SESSION['username'] = $username;
        $success = true;
    } else {
        $errors[] = "Failed to update profile";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profile</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="profile-container">
        <h2>Profile Settings</h2>
        
        <?php if ($success): ?>
            <div class="success">Profile updated successfully!</div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <?php if ($user['profile_photo']): ?>
                <div class="profile-photo">
                    <img src="../../uploads/profiles/<?php echo $user['profile_photo']; ?>" alt="Profile Photo">
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label>Profile Photo:</label>
                <input type="file" name="profile_photo" accept="image/*">
            </div>
            
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" value="<?php echo $user['username']; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo $user['email']; ?>" required>
            </div>
            
            <button type="submit">Update Profile</button>
        </form>
        
        <p><a href="change_password.php">Change Password</a></p>
        <p><a href="../security/logout.php">Logout</a></p>
    </div>
</body>
</html> 
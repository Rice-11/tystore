<?php
session_start();
require_once '../config.php';

// Check for valid session if not it goes to login page
if (!isset($_SESSION['valid'])) {
    header("Location: ../account/login.php");
    exit();
}

// Get user data from database
$user_id = $_SESSION['id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($result);

// Creation date
$created_date = new DateTime($user_data['created_at']);
$formatted_date = $created_date->format('F j, Y');

// PFP upload
if (isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

    if (in_array($file['type'], $allowed_types)) {
        $upload_dir = 'uploads/profile_pictures/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $filename = $user_id . '_' . time() . '_' . basename($file['name']);
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // Idk man i can't store the image on database only can store path
            $update_query = "UPDATE users SET profile_picture = ? WHERE id = ?";
            $stmt = mysqli_prepare($con, $update_query);
            mysqli_stmt_bind_param($stmt, "si", $filename, $user_id);
            mysqli_stmt_execute($stmt);

            // Update session data
            $user_data['profile_picture'] = $filename;
            $success_message = "Profile picture updated successfully!";
        }
    } else {
        $error_message = "Invalid file type. Please upload JPG, PNG or GIF files only.";
    }
}

// 2FA toggle
if (isset($_POST['toggle_2fa'])) {
    $new_2fa_status = $_POST['2fa_status'] === '1' ? 1 : 0;
    $update_query = "UPDATE users SET has_2fa = ? WHERE id = ?";
    $stmt = mysqli_prepare($con, $update_query);
    mysqli_stmt_bind_param($stmt, "ii", $new_2fa_status, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        echo "success";
        exit;
    } else {
        echo "error";
        exit;
    }
}

// updates profiles 
if (isset($_POST['update_profile'])) {
    $new_email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $new_age = filter_var($_POST['age'], FILTER_SANITIZE_NUMBER_INT);

    if (filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $update_query = "UPDATE users SET email = ?, age = ? WHERE id = ?";
        $stmt = mysqli_prepare($con, $update_query);
        mysqli_stmt_bind_param($stmt, "sii", $new_email, $new_age, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            $user_data['email'] = $new_email;
            $user_data['age'] = $new_age;
            $success_message = "Profile information updated successfully!";
        }
    } else {
        $error_message = "Invalid email format!";
    }
}

// Updates password
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($current_password === $user_data['password'] && $new_password === $confirm_password) {
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($con, $update_query);
        mysqli_stmt_bind_param($stmt, "si", $new_password, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Password changed successfully!";
        }
    } else {
        $error_message = "Invalid current password or passwords don't match!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings - <?php echo htmlspecialchars($user_data['username']); ?></title>
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../footer.css">
    <link rel="stylesheet" href="../profile.css">
    <link rel="stylesheet" href="../css/alogin.css">
</head>

<body>
    <?php include("../header.php"); ?>

    <div class="profile-container">
        <div class="profile-sidebar">
            <form id="profile-pic-form" method="post" enctype="multipart/form-data">
                <div class="profile-picture">
                    <img src="<?php echo $user_data['profile_picture'] ? '../uploads/profile_pictures/' . htmlspecialchars($user_data['profile_picture']) : '../accountimages/default-avatar.png'; ?>"
                        alt="Profile Picture" id="profile-pic">
                    <input type="file" name="profile_picture" id="profile-pic-upload" accept="image/*" style="display: none">
                </div>
            </form>
            <div class="profile-info">
                <div class="welcome-text">Welcome, <?php echo htmlspecialchars($user_data['username']); ?></div>
                <div class="user-email"><?php echo htmlspecialchars($user_data['email']); ?></div>
                <div class="join-date">Member since <?php echo $formatted_date; ?></div>
            </div>
        </div>

        <div class="settings-main">
            <div class="settings-section">
                <h2 class="section-title">Profile Information</h2>
                <form method="post">
                    <div class="field input">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    </div>
                    <div class="field input">
                        <label>Age</label>
                        <input type="number" name="age" value="<?php echo htmlspecialchars($user_data['age']); ?>" required>
                    </div>
                    <button type="submit" name="update_profile" class="btn-primary">Update Profile</button>
                </form>
            </div>

            <div class="settings-section">
                <h2 class="section-title">Security Settings</h2>

                <form method="post" class="toggle-switch" id="2faForm">
                    <label>Two-Factor Authentication</label>
                    <label class="toggle-btn">
                        <input type="checkbox" name="2fa_status" id="2faToggle" class="hidden"
                            <?php echo $user_data['has_2fa'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </form>

                <script>
                    document.getElementById('2faToggle').addEventListener('change', function(e) {
                        const formData = new FormData();
                        formData.append('toggle_2fa', '1');
                        formData.append('2fa_status', this.checked ? '1' : '0');

                        fetch('../profile.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.text())
                            .then(data => {
                                if (data !== 'success') {
                                    this.checked = !this.checked;
                                }
                            })
                            .catch(() => {
                                this.checked = !this.checked;
                            });
                    });
                </script>

                <form method="post" class="password-form">
                    <div class="field input">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="field input">
                        <label>New Password</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="field input">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn-primary">Change Password</button>
                </form>
            </div>

            <div class="settings-section">
                <h2 class="section-title">Notification Preferences</h2>
                <div class="notification-setting">
                    <label class="checkbox-label">
                        <input type="checkbox" id="email_notifications" checked>
                        <span>Email notifications for orders</span>
                    </label>
                </div>
                <div class="notification-setting">
                    <label class="checkbox-label">
                        <input type="checkbox" id="promotional_emails" checked>
                        <span>Promotional emails and offers</span>
                    </label>
                </div>
                <div class="notification-setting">
                    <label class="checkbox-label">
                        <input type="checkbox" id="security_alerts" checked>
                        <span>Security alerts</span>
                    </label>
                </div>
            </div>

            <div class="settings-section">
                <h2 class="section-title">Privacy Settings</h2>
                <div class="notification-setting">
                    <label class="checkbox-label">
                        <input type="checkbox" id="profile_visible" checked>
                        <span>Make profile visible to other users</span>
                    </label>
                </div>
                <div class="notification-setting">
                    <label class="checkbox-label">
                        <input type="checkbox" id="show_activity" checked>
                        <span>Show my activity status</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <?php include("../foot.php"); ?>

    <script>
        // Handle pfp upload
        document.getElementById('profile-pic-upload').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-pic').src = e.target.result;
                    // Submit the form automatically when a file is selected
                    document.getElementById('profile-pic-form').submit();
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        document.querySelector('.profile-picture').addEventListener('click', () => {
            document.getElementById('profile-pic-upload').click();
        });
    </script>
</body>

</html>
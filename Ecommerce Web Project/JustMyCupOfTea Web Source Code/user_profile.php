<?php
session_start();
if (!isset($_SESSION["member_id"])) {
    header("Location: login.php");
    exit();
}

$member_id = $_SESSION["member_id"];

$config = parse_ini_file('/var/www/private/db-config.ini');
$conn = new mysqli($config['servername'], $config['username'], $config['password'], $config['dbname']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT fname, lname, email, password, 2fa_secret FROM Justmycupoftea_members WHERE member_id = $member_id");
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    die("User not found.");
}

$has2FA = !empty($user['2fa_secret']);
$stored_hashed_password = $user['password'];

// Unlock check
$unlocked = false;
if (isset($_SESSION['unlocked_until']) && time() < $_SESSION['unlocked_until']) {
    $unlocked = true;
} else {
    unset($_SESSION['unlocked_until']);
}

// For unlocking the editing of profile
if (isset($_POST['unlock_profile']) && isset($_POST['unlock_password'])) {
    if (password_verify($_POST['unlock_password'], $stored_hashed_password)) {
        $_SESSION['unlocked_until'] = time() + 60; // 1 minute for editing
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $update_message = "Incorrect password to unlock profile.";
        $update_message_class = "error";
    }
}

// Handle update profile
if (isset($_POST['update_profile']) && $unlocked) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];

    $emailCheck = $conn->prepare("SELECT member_id FROM Justmycupoftea_members WHERE email = ? AND member_id != ?");
    $emailCheck->bind_param("si", $email, $member_id);
    $emailCheck->execute();
    $checkResult = $emailCheck->get_result();
    if ($checkResult->num_rows > 0) {
        $update_message = "Email is already in use.";
        $update_message_class = "error";
        $emailCheck->close();
    } else {
        $emailCheck->close();
        $stmt = $conn->prepare("UPDATE Justmycupoftea_members SET fname = ?, lname = ?, email = ? WHERE member_id = ?");
        $stmt->bind_param("sssi", $fname, $lname, $email, $member_id);

        if ($stmt->execute()) {
            $update_message = "Profile updated successfully.";
            $update_message_class = "success";
            $result = $conn->query("SELECT fname, lname, email, password, 2fa_secret FROM Justmycupoftea_members WHERE member_id = $member_id");
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
            }
        } else {
            $update_message = "Error updating profile.";
            $update_message_class = "error";
        }
        $stmt->close();
    }
}

// Password reset
if (isset($_POST['reset_password']) && $unlocked) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE Justmycupoftea_members SET password = ? WHERE member_id = ?");
        $stmt->bind_param("si", $hashed_password, $member_id);

        if ($stmt->execute()) {
            $password_message = "Password reset successfully.";
            $password_message_class = "success";
        } else {
            $password_message = "Error resetting password.";
            $password_message_class = "error";
        }
        $stmt->close();
    } else {
        $password_message = "Passwords do not match.";
        $password_message_class = "error";
    }
}

// 2FA Reset or Delete
if ($unlocked) {
    if (isset($_POST['reset_2fa'])) {
        $_SESSION["reset_2fa"] = true;
        header("Location: setup_2fa.php");
        exit();
    }

    else if (isset($_POST['delete_2fa'])) {
        $_SESSION["delete_2fa"] = true;
        header("Location: setup_2fa.php");
        exit();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<?php include "inc/head.inc.php"; ?>
<link rel="stylesheet" href="css/user_profile.css">
<title>User Profile</title>
<body>
<?php include "inc/nav.inc.php"; ?>
<div class="profile-container">
    <div class="container">
        <h2>User Profile</h2>

        <?php if (isset($update_message)): ?>
            <div class="message <?= $update_message_class; ?>"><?= $update_message; ?></div>
        <?php endif; ?>
        <?php if (isset($password_message)): ?>
            <div class="message <?= $password_message_class; ?>"><?= $password_message; ?></div>
        <?php endif; ?>

        <?php if (!$unlocked): ?>
            <form method="post">
                <label for="unlock_password">Enter Password to Unlock Profile:</label>
                <input type="password" name="unlock_password" id="unlock_password" required>
                <input type="submit" name="unlock_profile" value="Unlock Profile">
            </form>

            <fieldset disabled class="disabled-form">
                <label>First Name:</label>
                <input type="text" value="<?= $user['fname']; ?>">
                <label>Last Name:</label>
                <input type="text" value="<?= $user['lname']; ?>">
                <label>Email:</label>
                <input type="email" value="<?= $user['email']; ?>">
                <label>Password:</label>
                <input type="password" value="••••••••">
                <label>2FA Status:</label>
                <input type="text" value="<?= $has2FA ? 'Enabled ✅' : 'Not Enabled ❌'; ?>">
            </fieldset>

            <?php if (!$has2FA): ?>
                <form method="get" action="setup_2fa.php">
                    <input type="submit" value="Set Up 2FA">
                </form>
            <?php endif; ?>

        <?php else: ?>
            <p class="unlocked-msg">🔓 Edit Settings Unlocked (expires in <span id="countdown"></span>s)</p>

            <form method="post">
                <label for="fname">First Name:</label>
                <input type="text" name="fname" value="<?= htmlspecialchars($user['fname'], ENT_QUOTES, 'UTF-8'); ?>" required>

                <label for="lname">Last Name:</label>
                <input type="text" name="lname" value="<?= htmlspecialchars($user['lname'], ENT_QUOTES, 'UTF-8'); ?>">

                <label for="email">Email:</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" required>

                <input type="submit" name="update_profile" value="Update Profile">
            </form>

            <form method="post" id="resetPasswordForm">
                <label for="new_password">New Password (Must be 8 characters long, including uppercase, lowercase, number, and special character):</label>
                <input type="password" name="new_password" id="new_password" required
                       pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$"
                       title="Must be at least 8 characters long and include uppercase, lowercase, number, and special character.">

                <label for="confirm_password">Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>

                <input type="submit" name="reset_password" value="Reset Password">
            </form>

            <?php if ($has2FA): ?>
                <form method="post">
                    <p>Would you like to reset your 2FA code?</p>
                    <input type="submit" name="reset_2fa" value="Reset 2FA">
                </form>
                <form method="post">
                    <p>Would you like to disable 2FA? (Not Recommended)</p>
                    <input type="submit" name="delete_2fa" value="Delete 2FA">
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php include "inc/footer.inc.php"; ?>

<?php if ($unlocked): ?>
<script>
    let countdown = <?php echo $_SESSION['unlocked_until'] - time(); ?>;
    const countdownEl = document.getElementById("countdown");
    const interval = setInterval(() => {
        countdown--;
        if (countdownEl) countdownEl.textContent = countdown;
        if (countdown <= 0) {
            clearInterval(interval);
            location.reload();
        }
    }, 1000);
</script>
<?php endif; ?>

<script>
    // Password Validation for reset password form
    const passwordInput = document.getElementById('new_password');
    const confirmInput = document.getElementById('confirm_password');

    function validatePasswordMatch() {
        confirmInput.setCustomValidity(
            passwordInput.value !== confirmInput.value ? "Passwords do not match" : ""
        );
    }

    passwordInput.addEventListener('input', validatePasswordMatch);
    confirmInput.addEventListener('input', validatePasswordMatch);
</script>
</body>
</html>
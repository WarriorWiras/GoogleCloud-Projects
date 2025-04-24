<?php
session_start();
require 'vendor/autoload.php';
use Sonata\GoogleAuthenticator\GoogleAuthenticator;

// Redirect if not logged in
if (!isset($_SESSION["email"])) {
    header("Location: login.php");
    exit();
}

$config = parse_ini_file('/var/www/private/db-config.ini');
$conn = new mysqli($config['servername'], $config['username'], $config['password'], $config['dbname']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch existing secret from DB
$stmt = $conn->prepare("SELECT `2fa_secret` FROM Justmycupoftea_members WHERE email = ?");
$stmt->bind_param("s", $_SESSION["email"]);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$secret = $row["2fa_secret"] ?? "";

$gAuth = new GoogleAuthenticator();


// Handle delete 2FA request
if (isset($_SESSION["delete_2fa"]) && $_SESSION["delete_2fa"]) {
    $stmt = $conn->prepare("UPDATE Justmycupoftea_members SET `2fa_secret` = NULL WHERE email = ?");
    $stmt->bind_param("s", $_SESSION["email"]);
    $stmt->execute();
    $stmt->close();
    unset($_SESSION["delete_2fa"]);
    header("Location: user_profile.php"); // redirect back
    exit();
}

// If no secret yet, generate one and save it OR if user chose to reset from profile settings
if (empty($secret) || (isset($_SESSION["reset_2fa"]) && $_SESSION["reset_2fa"])) {
    $secret = $gAuth->generateSecret();

    $stmt = $conn->prepare("UPDATE Justmycupoftea_members SET `2fa_secret` = ? WHERE email = ?");
    $stmt->bind_param("ss", $secret, $_SESSION["email"]);
    $stmt->execute();

    unset($_SESSION["reset_2fa"]);
}
$stmt->close();
$conn->close();

// Create QR code
$qrUrl = $gAuth->getUrl($_SESSION["email"], 'JustMyCupOfTea', $secret);

// Verification
$error = "";
$verified = false;
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["test_code"])) {
    $code = $_POST["test_code"];
    if ($gAuth->checkCode($secret, $code)) {
        $verified = true;
    } else {
        $error = "Invalid code. Please try again.";
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="css/2fa.css">
    <meta charset="UTF-8">
    <title>Set up Two-Factor Authentication</title>
</head>
<body>
<div class="container-2fa">
    <h2>Set up Google Authenticator</h2>
    <p>Scan this QR code in your Google Authenticator app:</p>
    <img src="<?php echo $qrUrl; ?>" alt="QR Code"><br><br>
    <p>Or manually enter this secret: <strong><?php echo $secret; ?></strong></p>

    <!-- Test 2FA Code -->
    <form method="POST" class="test-form">
        <label for="test_code">Test your 6-digit code:</label><br>
        <input type="text" name="test_code" id="test_code" required maxlength="6" pattern="\d{6}">
        <button type="submit">Verify Code</button>
    </form>

    <?php if ($verified): ?>
        <p class="success">✅ Code is valid!</p>
    <?php elseif (!empty($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    <p>
        <a href="index.php" onclick="return confirm('Have you saved your 2FA code? You will not be able to login without it!')">
            I have saved my authenticator code
        </a>
    </p>
</div>
</body>
</html>

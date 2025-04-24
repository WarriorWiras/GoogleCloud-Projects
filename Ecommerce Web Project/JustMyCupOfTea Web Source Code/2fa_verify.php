<?php
session_start();
require 'vendor/autoload.php';
use Sonata\GoogleAuthenticator\GoogleAuthenticator;

// Redirect if not logged in
if (!isset($_SESSION["email"])) {
    header("Location: login.php");
    exit();
}

// Load secret from DB
$config = parse_ini_file('/var/www/private/db-config.ini');
$conn = new mysqli($config['servername'], $config['username'], $config['password'], $config['dbname']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT 2fa_secret FROM Justmycupoftea_members WHERE email = ?");
$stmt->bind_param("s", $_SESSION["email"]);
$stmt->execute();
$result = $stmt->get_result();
$secret = "";
if ($row = $result->fetch_assoc()) {
    $secret = $row["2fa_secret"];
}
$stmt->close();
$conn->close();

// Handle submission
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = $_POST["code"];
    $gAuth = new GoogleAuthenticator();

    if ($gAuth->checkCode($secret, $code)) {
        $_SESSION["2fa_verified"] = true;
        unset($_SESSION["2fa_pending"]);
        unset($_SESSION["2fa_email"]);
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid authentication code.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<link rel="stylesheet" href="css/2fa.css">
    <meta charset="UTF-8">
    <title>2FA Verification</title>
</head>
<body>
<div class="container-2fa">
    <h2>Two-Factor Authentication</h2>
    <?php if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="POST">
        <label>Enter 6-digit code:</label><br>
        <input type="text" name="code" required maxlength="6" pattern="\d{6}"><br><br>
        <button type="submit">Verify</button>
    </form>
    </div>
</body>
</html>

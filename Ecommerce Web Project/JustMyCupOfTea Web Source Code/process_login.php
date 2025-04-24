<?php
// Start session and output buffering
session_start();
ob_start();

// Detect AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';


$email = $pwd = "";
$errorMsg = "";
$success = true;

$apiKeys = parse_ini_file('/var/www/private/api-keys.ini');

// Validate email
if (empty($_POST["email"])) {
    $errorMsg .= "Email is required.<br>";
    $success = false;
} else {
    $email = sanitize_input($_POST["email"]);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg .= "Invalid email format.<br>";
        $success = false;
    }
}

// Validate password
if (empty($_POST["pwd"])) {
    $errorMsg .= "Password is required.<br>";
    $success = false;
} else {
    $pwd = $_POST["pwd"];
}

// Validate reCAPTCHA
$recaptchaSecret = $apiKeys['google_secret_key'];  // ReCAPTCHA key
$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

if (empty($recaptchaResponse)) {
    die("CAPTCHA verification failed: No token received.");
}

$recaptchaURL = "https://www.google.com/recaptcha/api/siteverify";
$recaptchaVerify = file_get_contents($recaptchaURL . "?secret=" . $recaptchaSecret . "&response=" . $recaptchaResponse);
$recaptchaData = json_decode($recaptchaVerify, true);

// Log reCAPTCHA API response for debugging
error_log("reCAPTCHA Response: " . json_encode($recaptchaData));

if (!$recaptchaData["success"]) {
    die("CAPTCHA verification failed: " . json_encode($recaptchaData));
}

// Authenticate
if ($success) {
    authenticateUser($email, $pwd, $errorMsg, $success);
}



if ($isAjax) {
    echo $success ? "success" : $errorMsg;
    ob_end_flush();
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 2rem; }
    </style>
</head>
<body>
</body>
</html>
<?php
ob_end_flush();

// Sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Authenticate user
function authenticateUser($email, $pwd, &$errorMsg, &$success) {
    global $fname, $lname;

    $config = parse_ini_file('/var/www/private/db-config.ini');
    if (!$config) {
        $errorMsg = "Failed to read database config file.";
        $success = false;
        return;
    }

    $conn = new mysqli(
        $config['servername'],
        $config['username'],
        $config['password'],
        $config['dbname']
    );

    if ($conn->connect_error) {
        $errorMsg = "Database connection failed: " . $conn->connect_error;
        $success = false;
        return;
    }

    $stmt = $conn->prepare("SELECT member_id, fname, lname, password, role, 2fa_secret FROM Justmycupoftea_members WHERE email=?");
    if (!$stmt) {
        $errorMsg = "Prepare statement failed: " . $conn->error;
        $success = false;
        return;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $fname = $row["fname"];
        $lname = $row["lname"];
        $pwd_hashed = $row["password"];

        if (password_verify($pwd, $pwd_hashed)) {
            $_SESSION["member_id"] = $row["member_id"];
            $_SESSION["fname"] = $fname;
            $_SESSION["lname"] = $lname;
            $_SESSION["email"] = $email;
            $_SESSION["role"] = $row["role"];

            //if 2FA is enabled, check for it
            if (!is_null($row["2fa_secret"]) && $row["2fa_secret"] !== '') {
                $_SESSION["2fa_pending"] = true;
                $_SESSION["2fa_email"] = $email;
                $success = false;
                $errorMsg = "2FA_REQUIRED";
                return;
            } else {
                $_SESSION["2fa_verified"] = true;
            }

            if (isset($_POST['remember'])) {
                setcookie("email", $email, time() + (86400 * 30), "/");
            }
        } else {
            $errorMsg = "Email not found or password doesn't match.";
            $success = false;
        }
    } else {
        $errorMsg = "Email not found or password doesn't match.";
        $success = false;
    }

    $stmt->close();
    $conn->close();
}
?>

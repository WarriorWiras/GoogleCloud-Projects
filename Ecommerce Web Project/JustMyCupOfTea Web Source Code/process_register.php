<?php
// Start output buffering (optional)
ob_start();

$apiKeys = parse_ini_file('/var/www/private/api-keys.ini');

// Detect if request is via AJAX
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

// Initialize variables
$fname = $lname = $email = $pwd = $pwd_confirm = "";
$errorMsg = "";
$success = true;

// === 1) Retrieve form data & validate ===

// First name
if (!empty($_POST["fname"])) {
    $fname = sanitize_input($_POST["fname"]);
}

//  Last name
    $lname = sanitize_input($_POST["lname"]);

// Required: Email
if (empty($_POST["email"])) {
    $errorMsg .= "Email is required";
    $success = false;
} else {
    $email = sanitize_input($_POST["email"]);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg .= "Invalid email format";
        $success = false;
    }
}

// Required: Password
if (empty($_POST["pwd"])) {
    $errorMsg .= "Password is required";
    $success = false;
} else {
    $pwd = $_POST["pwd"];
}

// Required: Confirm Password
if (empty($_POST["pwd_confirm"])) {
    $errorMsg .= "Confirmation password is required";
    $success = false;
} else {
    $pwd_confirm = $_POST["pwd_confirm"];
}

// Check if passwords match
if (!empty($pwd) && !empty($pwd_confirm) && ($pwd !== $pwd_confirm)) {
    $errorMsg .= "Passwords do not match";
    $success = false;
}

// If everything is still true so far, hash the password (we’ll insert later)
if ($success) {
    $hashedPwd = password_hash($pwd, PASSWORD_DEFAULT);
}

// === 2) SERVER-SIDE reCAPTCHA VERIFICATION ===


if (empty($_POST['g-recaptcha-response'])) {
    $errorMsg .= "No reCAPTCHA token received";
    $success = false;
} else {
    // secret key
    $recaptchaSecret = $apiKeys['google_secret_key'];
    
    $recaptchaResponse = $_POST['g-recaptcha-response'];
    $recaptchaURL = "https://www.google.com/recaptcha/api/siteverify";
    
    // Call Google's verify API
    $verify = file_get_contents(
        $recaptchaURL . "?secret=" . $recaptchaSecret . "&response=" . $recaptchaResponse
    );
    $captchaSuccess = json_decode($verify, true);

    // Check "success" field from Google
    if (empty($captchaSuccess["success"]) || !$captchaSuccess["success"]) {
        // You can inspect $captchaSuccess for error codes if needed
        $errorMsg .= "CAPTCHA verification failed. Please try again.";
        $success = false;
    }
}

// === 3) Only attempt DB insertion if $success is still true ===
if ($success) {
    saveMemberToDB($fname, $lname, $email, $hashedPwd, $errorMsg, $success);
}

// === 4) Return a response depending on AJAX or not ===
if ($isAjax) {
    // AJAX request: Return plain text
    echo $success ? "success" : $errorMsg;
    ob_end_flush();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration Process</title>
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

/**
 * Sanitize input
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Attempt to save the member to DB
 */
function saveMemberToDB($fname, $lname, $email, $pwd_hashed, &$errorMsg, &$success) {
    // Load database credentials from config
    $config = parse_ini_file('/var/www/private/db-config.ini');
    if (!$config) {
        $errorMsg .= "Failed to read database config file.";
        $success = false;
        return;
    }

    // Create database connection
    $conn = new mysqli(
        $config['servername'],
        $config['username'],
        $config['password'],
        $config['dbname']
    );

    // Check connection
    if ($conn->connect_error) {
        $errorMsg .= "Connection failed: " . $conn->connect_error ;
        $success = false;
        return;
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT email FROM Justmycupoftea_members WHERE email=?");
    if (!$stmt) {
        $errorMsg .= "Prepare statement failed: " . $conn->error;
        $success = false;
        return;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $errorMsg .= "Email already exists.";
        $success = false;
        $stmt->close();
        $conn->close();
        return;
    }
    $stmt->close();

    // Insert new user
    $stmt = $conn->prepare(
        "INSERT INTO Justmycupoftea_members (fname, lname, email, password, 2fa_secret) 
         VALUES (?, ?, ?, ?, NULL)"
    );
    if (!$stmt) {
        $errorMsg .= "Prepare statement failed: " . $conn->error;
        $success = false;
        return;
    }

    $stmt->bind_param("ssss", $fname, $lname, $email, $pwd_hashed);

    if (!$stmt->execute()) {
        $errorMsg .= "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        $success = false;
    }

    $stmt->close();
    $conn->close();
}
?>

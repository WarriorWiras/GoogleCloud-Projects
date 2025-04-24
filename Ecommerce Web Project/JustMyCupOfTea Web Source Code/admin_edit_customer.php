<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

// Connect to database
$config = parse_ini_file('/var/www/private/db-config.ini');
$conn = new mysqli($config['servername'], $config['username'], $config['password'], $config['dbname']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$member_id = $_GET['id'];
$message = ""; // Initialize message variable

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    if ($conn->query("UPDATE Justmycupoftea_members SET fname = '$fname', lname = '$lname', email = '$email', role = '$role' WHERE member_id = $member_id")) {
        $message = "<p class='success-message'>Customer updated successfully!</p>";
    } else {
        $message = "<p class='error-message'>Error updating customer: " . $conn->error . "</p>";
    }
}

// Fetch customer data
$result = $conn->query("SELECT * FROM Justmycupoftea_members WHERE member_id = $member_id");
$customer = $result->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<?php include "inc/head.inc.php"; ?>
<title>Edit Customer</title>
<style>
    body {
        font-family: sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f8f8f8; /* Light gray background */
    }

    .container {
        width: 80%;
        margin: 20px auto;
        text-align: center; /* Center the heading */
    }

    h1 {
        color: #501287; /* Dark pink heading */
        margin-top: 20px;
        margin-bottom: 20px;
    }

    .form-container { /* Added styles for the form container */
        background-color: #E6A8D7; /* Light purple background */
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        text-align: left; /* Align form elements to the left */
    }

    form label {
        display: block;
        margin-bottom: 5px;
        color: #333;
    }

    form input[type="text"],
    form input[type="email"],
    form select {
        width: calc(100% - 22px);
        padding: 10px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }

    form select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url('data:image/svg+xml;utf8,<svg fill="%23333" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
        background-repeat: no-repeat;
        background-position: right 10px center;
    }

    form button[type="submit"] {
        background-color: #501287; /* Purple button */
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        display: block;
        margin: 20px auto 0;
    }

    form button[type="submit"]:hover {
        background-color: #501287; /* Darker purple on hover */
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .container {
            width: 95%;
        }
    }

    .success-message {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 10px;
        text-align: center;
    }

    .error-message {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 10px;
        text-align: center;
    }
</style>
<body>
    <?php include "inc/nav.inc.php"; ?>
    <div class="container">
        <h1>Edit Customer</h1>
        <?php echo $message; ?> <form method="POST" class="form-container">
            <label>First Name:</label>
            <input type="text" name="fname" value="<?php echo $customer['fname']; ?>"><br>
            <label>Last Name:</label>
            <input type="text" name="lname" value="<?php echo $customer['lname']; ?>"><br>
            <label>Email:</label>
            <input type="email" name="email" value="<?php echo $customer['email']; ?>"><br>
            <label>Role:</label>
            <select name="role">
                <option value="user" <?php if ($customer['role'] === 'user') echo 'selected'; ?>>User</option>
                <option value="admin" <?php if ($customer['role'] === 'admin') echo 'selected'; ?>>Admin</option>
            </select><br>
            <button type="submit">Update</button>
        </form>
    </div>
    <?php include "inc/footer.inc.php"; ?>
</body>
</html>
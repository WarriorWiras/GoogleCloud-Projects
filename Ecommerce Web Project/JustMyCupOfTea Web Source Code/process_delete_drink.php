<?php
session_start();

// Check if the user is an admin
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

// Connect to the database
$config = parse_ini_file('/var/www/private/db-config.ini');
$conn = new mysqli($config['servername'], $config['username'], $config['password'], $config['dbname']);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the drink_id is provided
if (isset($_GET['id'])) {
    $drink_id = $_GET['id'];

    // Use prepared statements to prevent SQL injection
    $sql = "DELETE FROM Justmycupoftea_menu WHERE drink_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $drink_id); // "i" indicates an integer

        if ($stmt->execute()) {
            // Deletion successful
            header("Location: admin_menu.php?success=Drink removed");
            exit(); // Ensure no further code is executed
        } else {
            // Deletion failed
            echo "Error deleting drink: " . $stmt->error;
        }

        $stmt->close();
    } else {
        // Prepare statement failed
        echo "Error preparing statement: " . $conn->error;
    }
} else {
    // drink_id not provided
    echo "Drink ID not specified.";
}

$conn->close();
?>
<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

// Connect to database
$config = parse_ini_file('/var/www/private/db-config.ini');
$conn = new mysqli($config['servername'], $config['username'], $config['password'], $config['dbname']);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$drink_id = $_POST["drink_id"];
$name = $_POST["name"];
$category = $_POST["category"];
$price = $_POST["price"];

// Handle image upload (if a new image is provided)
$imagePath = "";
if ($_FILES["image"]["name"]) {
    $targetDir = "images/drinks/";
    $imagePath = $targetDir . basename($_FILES["image"]["name"]);
    move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath);
}

// Prepare the update query
if ($imagePath) {
    // If a new image was uploaded, update the image path
    $stmt = $conn->prepare("UPDATE Justmycupoftea_menu SET name = ?, category = ?, price = ?, image = ? WHERE drink_id = ?");
    $stmt->bind_param("ssdsi", $name, $category, $price, $imagePath, $drink_id);
} else {
    // If no new image was uploaded, don't update the image path
    $stmt = $conn->prepare("UPDATE Justmycupoftea_menu SET name = ?, category = ?, price = ? WHERE drink_id = ?");
    $stmt->bind_param("ssdi", $name, $category, $price, $drink_id);
}

if ($stmt->execute()) {
    header("Location: admin_update_drink.php?id=$drink_id&success=Drink updated"); 
} else {
    header("Location: admin_update_drink.php?id=$drink_id&error=Failed to update drink"); 
}

$stmt->close();
$conn->close();
?>
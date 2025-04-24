<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

// Connect to the database
$config = parse_ini_file('/var/www/private/db-config.ini');
$conn = new mysqli($config['servername'], $config['username'], $config['password'], $config['dbname']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$name = $_POST["name"];
$category = $_POST["category"];
$price = $_POST["price"];

// This is for the image rn is just copying path
$imagePath = "";
if ($_FILES["image"]["name"]) {
    $targetDir = "images/drinks/";
    $imagePath = $targetDir . basename($_FILES["image"]["name"]);
    move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath);
}

$stmt = $conn->prepare("INSERT INTO Justmycupoftea_menu (name, category, price, image) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssds", $name, $category, $price, $imagePath);

if ($stmt->execute()) {
    header("Location: admin_add_drink.php?success=Drink added");
} else {
    header("Location: admin_add_drink.php?error=Failed to add drink");
}

$stmt->close();
$conn->close();
?>

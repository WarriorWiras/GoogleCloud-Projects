<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

// Connect to the database
$config = parse_ini_file('/var/www/private/db-config.ini');
$conn = new mysqli($config['servername'], $config['username'], $config['password'], $config['dbname']);

// Delete drink from database
$id = $_GET["id"];
$conn->query("DELETE FROM Justmycupoftea_menu WHERE drink_id = $id");

header("Location: admin_add_drink.php?success=Drink removed");
?>

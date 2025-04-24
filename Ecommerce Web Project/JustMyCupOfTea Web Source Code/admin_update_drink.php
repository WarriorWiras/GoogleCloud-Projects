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

// Get drink data
$drink_id = $_GET["id"];
$result = $conn->query("SELECT * FROM Justmycupoftea_menu WHERE drink_id = $drink_id");
$drink = $result->fetch_assoc();

if (!$drink) {
    die("Drink not found");
}

// Check for success or error messages
$successMessage = isset($_GET['success']) ? $_GET['success'] : '';
$errorMessage = isset($_GET['error']) ? $_GET['error'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<?php include "inc/head.inc.php"; ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Drink</title>
    <style>
        body {
            font-family: Roboto, sans-serif;
            background-color: white;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .container {
            padding: 2rem;
        }

        .card {
            background-color: #E6A8D7;
            border: none;
            border-radius: 10px;
        }

        .form-label {
            color: #501287;
        }

        .form-control, .form-select {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .button-container {
            display: flex;
            justify-content: center;
        }

        .btn-primary {
            background-color: #501287;
            border-color: #501287;
            padding: 8px 16px;
            font-size: 14px;
            width: auto;
        }

        .btn-primary:hover {
            background-color: #7e57c2;
            border-color: #7e57c2;
        }

        .drink-img {
            max-width: 200px;
            height: auto;
        }

        .drink-img2 {
            max-width: 80px;
            height: auto;
        }

        a {
            color: #501287;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .text-center {
            text-align: center;
        }

        h1 {
            color: #501287;
        }

        .card form button.btn-primary { 
            width: auto !important; 
            padding: 8px 16px;
        }

        .drink-list {
            max-width: 700px;
            margin: 2rem auto;
        }

        .table {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .table th, .table td {
            text-align: center;
        }

        .btn-update {
            background-color: #501287; 
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            color: white;
        }

        .btn-update:hover {
            background-color: #7e57c2; 
        }

        .btn-danger {
            background-color: red;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            color: white;
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

        .action-links {
        text-align: center;
        margin-bottom: 20px;
        }

        .action-links a {
            color: #501287;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .action-links a:hover {
            background-color: #f0f0f0;
            text-decoration: underline;
        }
    </style>
</head>
<body>
<?php include "inc/nav.inc.php"; ?>
<div class="container">
        <h1 class="text-center">Update Drink</h1>

        <div class="action-links">
            <a href="admin_menu.php">Return to Drink Menu</a> | <a href="admin_add_drink.php">Add a New Drink</a>
        </div>

        <?php if ($successMessage): ?>
            <div class="success-message">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="error-message">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

    <div class="card p-4 mb-4">
        <form action="process_update_drink.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="drink_id" value="<?php echo $drink['drink_id']; ?>">

            <div class="mb-3">
                <label class="form-label">Drink Name:</label>
                <input type="text" name="name" class="form-control" value="<?php echo $drink['name']; ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Category:</label>
                <select name="category" class="form-select" required>
                    <option value="seasonal-special" <?php if ($drink['category'] === 'seasonal-special') echo 'selected'; ?>>Seasonal Special</option>
                    <option value="milk-tea" <?php if ($drink['category'] === 'milk-tea') echo 'selected'; ?>>Milk Tea</option>
                    <option value="fresh-milk" <?php if ($drink['category'] === 'fresh-milk') echo 'selected'; ?>>Fresh Milk</option>
                    <option value="macchiato" <?php if ($drink['category'] === 'macchiato') echo 'selected'; ?>>Macchiato</option>
                    <option value="flavored-tea" <?php if ($drink['category'] === 'flavored-tea') echo 'selected'; ?>>Flavored Tea</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Price ($):</label>
                <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $drink['price']; ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Image:</label>
                <input type="file" name="image" class="form-control">
                <img src="<?php echo $drink['image']; ?>" class="drink-img mt-2">
            </div>

            <button type="submit" class="btn btn-primary w-100">Update Drink</button>
            
        </form>
    </div>

    <div class="drink-list">
        <h3 class="text-center">Drink Menu</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM Justmycupoftea_menu");

                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td><img src='{$row['image']}' class='drink-img2'></td>
                            <td>{$row['name']}</td>
                            <td>{$row['category']}</td>
                            <td>\${$row['price']}</td>
                            <td>
                                <a href='admin_update_drink.php?id={$row['drink_id']}' class='btn btn-update btn-sm'>Update</a>
                            </td>
                            <td>
                                <a href='process_remove_drink.php?id={$row['drink_id']}' class='btn btn-danger btn-sm'>Remove</a>
                            </td>
                        </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include "inc/footer.inc.php"; ?>
</body>
</html>

<?php
$conn->close();
?>
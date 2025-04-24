<?php
// Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Connect to database
$config = parse_ini_file('/var/www/private/db-config.ini');
$conn = new mysqli($config['servername'], $config['username'], $config['password'], $config['dbname']);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch drinks from the database
$query = "SELECT * FROM Justmycupoftea_menu";
$result = $conn->query($query);

// Store drinks in an array
$menuItems = [];
while ($row = $result->fetch_assoc()) {
    $menuItems[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<?php include "inc/head.inc.php"; ?>

    <title>Just My Cup of Tea</title>
    <style>
        body {
            font-family: Roboto, sans-serif;
            font-weight: bold;
            margin: 0;
            padding: 0;
            background-color: white;
            color: #333;
        }

        main {
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 0;
        }

        .category-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            padding: 10px;
        }

        .category-button {
            background-color: #E6A8D7;
            padding: 20px 25px; 
            border: none;
            border-radius: 30px;
            cursor: pointer;
            color: white;
            font-weight: 500;
            text-align: center;
            font-size: 17px;
            min-width: 210px; 
            white-space: nowrap;
        }

        .category-button.active {
            background-color: #501287;
            color: white;
        }

        .menu-header {
        text-align: left;
        padding: 150px 0; 
        background-image: url('/images/menu_background.jpeg');
        background-size: cover;
        background-position: center;
        color: white;
        width: 100%; 
        margin-bottom: 10px; 
        }

        .menu-header h1 {
            font-size: 4em;
            color: #501287;
            margin-bottom: 5px;
            padding-left: 40px; 
        }

        .menu-header p {
            font-size: 1.1em;
            color: #501287;
            line-height: 1.6;
            max-width: 600px;
            margin: 0;
            padding-left: 40px; 
        }

        .menu-items-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 10px;
            margin-bottom: 10px;
        }

        .menu-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            justify-content: center;
            align-items: start;
        }

        .menu-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            background-color: #E6A8D7;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 250px;
            min-height: 350px;
            height: auto;
            overflow: hidden;
            margin: auto;
            transition: transform 0.3s ease-in-out;
        }

        .menu-item:hover {
            transform: scale(1.03);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .menu-item img {
            width: 100%;
            height: 230px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .menu-item h3 {
            color: #501287;
            font-size: 17px;
            margin-bottom: 10px;
        }

        .menu-item p {
            color: #555;
            font-size: 12px;
        }

        .menu-item .btn-primary {
            background-color: #501287;
            border-color: #501287;
            color: white;
        }

        .menu-item .btn-primary:hover {
            background-color: #7e57c2;
            border-color: #7e57c2;
        }

        .menu-item .card-text.text-primary.font-weight-bold {
        color: #501287 !important; 
        font-size: 1.1em;
    }
    </style>

<body>
    <?php include "inc/nav.inc.php"; ?>

    <div class="container">  <!-- Use a div instead of a second main -->
        <div class="menu-header">
            <h1>Menu</h1>
            <p>More than just boba. Explore our diverse menu featuring seasonal specials, creamy milk teas, refreshing fruit teas, and decadent macchiatos. There's something for everyone at JUST MY CUP OF TEA!</p>
        </div>

        <div class="category-container">
            <button class="category-button active" data-category="seasonal-special">Seasonal Special</button>
            <button class="category-button" data-category="milk-tea">Milk Tea</button>
            <button class="category-button" data-category="fresh-milk">Fresh Milk</button>
            <button class="category-button" data-category="macchiato">Macchiato</button>
            <button class="category-button" data-category="flavored-tea">Flavored Tea</button>
        </div>

        <div class="menu-items-container">
            <div class="menu-items">
                <?php foreach ($menuItems as $item) : ?>
                    <div class="menu-item" data-category="<?= $item['category'] ?>">
                        <img src="<?= $item['image'] ?>" alt="<?= $item['name'] ?>">
                        <h3><?= $item['name'] ?></h3>
                        <p class="card-text text-primary font-weight-bold">$<?= number_format($item['price'], 2) ?></p>
                        <form method="post" action="customize_drink.php">
                            <input type="hidden" name="drink_id" value="<?= $item['drink_id'] ?>">
                            <input type="hidden" name="item_name" value="<?= $item['name'] ?>">
                            <input type="hidden" name="item_price" value="<?= $item['price'] ?>">
                            <input type="hidden" name="item_image" value="<?= $item['image'] ?>">
                            <?php if (isset($_SESSION['member_id'])): ?>
                                <button type="submit" name="add_to_cart" class="btn btn-primary btn-block">Add to Cart</button>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <?php include "inc/footer.inc.php"; ?>
    <script src="js/menu.js"></script>
</body>


</html>

<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$drinkId = $_POST['drink_id'] ?? $_GET['id'] ?? null;

//Check if user is logged in
if (!isset($_SESSION['member_id'])) {
    header("Location: menu.php");
    exit();
}

// Define customization options
$sugar_levels = ["0%", "25%", "50%", "75%", "100%"];
$ice_levels = ["No Ice", "Less Ice", "Normal Ice", "Extra Ice"];

// Fetch toppings from database
try {
    $config = parse_ini_file('/var/www/private/db-config.ini');
    $pdo = new PDO("mysql:host={$config['servername']};dbname={$config['dbname']}", $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query to fetch toppings from the database
    $stmt = $pdo->prepare("SELECT topping_id, name, price, image FROM toppings");
    $stmt->execute();
    $toppings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If database error occurs, fallback to hardcoded toppings
    error_log("Database error: " . $e->getMessage());
    $toppings = [
        ["name" => "Pearl", "price" => 0.50, "image" => "BlackPearl.png"],
        ["name" => "White Pearl", "price" => 0.50, "image" => "WhitePearl.png"],
        ["name" => "Aloe Vera", "price" => 0.75, "image" => "AloeVera.png"],
        ["name" => "Grass Jelly", "price" => 0.75, "image" => "GrassJelly.png"],
        ["name" => "Red Bean", "price" => 1.00, "image"=> "RedBean.png"],
        ["name" => "Pudding", "price" => 1.00, "image" => "Pudding.png"]
    ];
}

// Initialize variables for drink details
$drinkId = $_POST['drink_id'] ?? $_GET['id'] ?? '';
$item_name = 'Unknown Drink';
$item_price = 0;
$item_image = '';
$item_toppings = '';

// Get drink details from POST data when coming from menu.php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $item_name = isset($_POST['item_name']) ? $_POST['item_name'] : 'Unknown Drink';
    $item_price = isset($_POST['item_price']) ? floatval($_POST['item_price']) : 0;
    $item_image = isset($_POST['item_image']) ? $_POST['item_image'] : '';
    $item_toppings = isset($_POST['item_toppings']) ? $_POST['item_toppings'] : '';
}
// Get details from GET parameters if coming from menu.php with GET
elseif (isset($_GET['name']) && isset($_GET['price'])) {
    $item_name = $_GET['name'];
    $item_price = floatval($_GET['price']);
    $item_image = isset($_GET['image']) ? $_GET['image'] : '';
    $item_toppings = isset($_GET['toppings']) ? $_GET['toppings'] : '';
}

// Handle form submission for adding customized drink to cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_customized_drink'])) {
    // Get the drink details
    $drinkId = isset($_POST['drink_id']) ? $_POST['drink_id'] : '';
    $drinkName = isset($_POST['drink_name']) ? $_POST['drink_name'] : '';
    $drinkPrice = isset($_POST['drink_price']) ? floatval($_POST['drink_price']) : 0;
    $drinkImage = isset($_POST['drink_image']) ? $_POST['drink_image'] : '';
    
    // Get the selected toppings
    $toppings_selected = isset($_POST['toppings']) ? $_POST['toppings'] : [];
    
    // Get the selected options
    $sugar = isset($_POST['sugar_level']) ? $_POST['sugar_level'] : 'Normal';
    $ice = isset($_POST['ice_level']) ? $_POST['ice_level'] : 'Normal';
    
    // Calculate additional cost for toppings
    $additionalCost = 0;
    foreach ($toppings_selected as $topping) {
        // Add appropriate cost for each topping
        foreach ($toppings as $t) {
            if ($t['name'] == $topping) {
                $additionalCost += $t['price'];
                break;
            }
        }
    }
    
    // Generate a unique cart item ID
    $cartItemId = uniqid();
    
    // Create the cart item array
    $cartItem = [
        'id' => $cartItemId,
        'drink_id' => $drinkId,
        'name' => $drinkName,
        'price' => $drinkPrice + $additionalCost,
        'image' => $drinkImage,
        'sugar_level' => $sugar,
        'ice_level' => $ice,
        'toppings' => $toppings_selected,
        'additional_cost' => $additionalCost,
        'total_price' => $drinkPrice + $additionalCost,
        'quantity' => 1
    ];
    
    // Initialize the cart if it doesn't exist
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Add the item to the cart 
    $_SESSION['cart'][] = $cartItem;
    
    
    // Make sure there is no output before this point
    // Redirect to the cart page
    header('Location: cart.php');
    exit();
}

// Debug information - you can remove this after fixing the issue
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_log("POST variables: " . print_r($_POST, true));
}

// Fix for spaces in image URLs
if ($item_image) {
    $item_image = str_replace(' ', '%20', $item_image);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Customize Your Drink - Just My Cup Of Tea</title>
    <?php include "inc/head.inc.php"; ?>
    <link rel="stylesheet" href="css/customize_drink.css">
</head>
<body>
    <?php include "inc/nav.inc.php"; ?>
    
    <header class="jumbotron text-center rounded-0">
        <h1 class="display-4">Customize Your Drink</h1>
        <h2>Make it exactly how you like it!</h2>
    </header>
    
    <main class="container my-5">
        <div class="row">
            <!-- Drink Preview Section -->
            <div class="col-md-5">
                <div class="card mb-4 shadow-sm">
                    <div>
                        <h3 class="card-header">Toppings:</h3>
                        <div id="selected-toppings-display" class="px-3 py-2 d-flex flex-wrap gap-2"></div>
                    </div>
                    <div id="drink-preview-container" class="position-relative">
                        <img src="<?php echo htmlspecialchars($item_image); ?>" 
                            class="card-img-top" 
                            id="drink-preview-image"
                            alt="<?php echo htmlspecialchars($item_name); ?>" 
                            style="aspect-ratio: 1/1; object-fit: cover; width: 100%;"
                            onerror="this.src='images/default-boba.jpg'">
                        <div id="toppings-container" class="position-absolute w-100 h-100 top-0 left-0 pointer-events-none"></div>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title"><?php echo htmlspecialchars($item_name); ?></h3>
                        <p class="card-text"><?php echo htmlspecialchars($item_toppings); ?></p>
                        <p class="card-text text-primary font-weight-bold">Base Price: $<?php echo number_format($item_price, 2); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Customization Options Section -->
            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h3 class="mb-0">Customize Your Drink</h3>
                    </div>
                    <div class="card-body">
                        <form method="post" action="customize_drink.php" id="customization-form">
                            <!-- Hidden fields to carry drink information -->
                            <input type="hidden" name="drink_id" value="<?php echo htmlspecialchars($drinkId); ?>">
                            <input type="hidden" name="drink_name" value="<?php echo htmlspecialchars($item_name); ?>">
                            <input type="hidden" name="drink_price" value="<?php echo htmlspecialchars($item_price); ?>">
                            <input type="hidden" name="drink_image" value="<?php echo htmlspecialchars($item_image); ?>">
                            <div id="selectedToppingsInputs"></div>
                            
                            <!-- Sugar Level Selection -->
                            <div class="form-group mb-4">
                                <label class="form-label fw-bold">Sugar Level</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($sugar_levels as $level): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="sugar_level" 
                                                id="sugar_<?php echo str_replace('%', '', $level); ?>" 
                                                value="<?php echo $level; ?>" 
                                                <?php echo ($level == "100%") ? "checked" : ""; ?>>
                                            <label class="form-check-label" for="sugar_<?php echo str_replace('%', '', $level); ?>">
                                                <?php echo $level; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Ice Level Selection -->
                            <div class="form-group mb-4">
                                <label class="form-label fw-bold">Ice Level</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($ice_levels as $level): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="ice_level" 
                                                id="ice_<?php echo str_replace(' ', '_', strtolower($level)); ?>" 
                                                value="<?php echo $level; ?>" 
                                                <?php echo ($level == "Normal Ice") ? "checked" : ""; ?>>
                                            <label class="form-check-label" for="ice_<?php echo str_replace(' ', '_', strtolower($level)); ?>">
                                                <?php echo $level; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Toppings Selection -->
                            <div class="form-group mb-4">
                                <label class="form-label fw-bold">Toppings (Click Or Drag To Drink)</label>
                                <div class="row">
                                    <?php foreach ($toppings as $topping): ?>
                                        <div class="col-md-4 col-6 mb-3">
                                            <div class="topping-item draggable-topping" 
                                                data-topping-name="<?php echo htmlspecialchars($topping['name']); ?>"
                                                data-topping-price="<?php echo htmlspecialchars($topping['price']); ?>"
                                                data-topping-image="<?php 
                                                    if (isset($topping['image'])) {
                                                        echo htmlspecialchars($topping['image']);
                                                    } else {
                                                        echo htmlspecialchars($topping['name'] . '.png');
                                                    }
                                                ?>">
                                                <div class="topping-inner">
                                                    <?php 
                                                    // Determine the image to use
                                                    $imagePath = "images/toppings/";
                                                    if (isset($topping['image'])) {
                                                        $imagePath .= $topping['image'];
                                                    } else {
                                                        // Default naming convention
                                                        $imagePath .= $topping['name'] . ".png";
                                                    }
                                                    ?>
                                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($topping['name']); ?>" class="img-fluid" style="height: 40px; width: auto;">
                                                    <div class="topping-name"><?php echo htmlspecialchars($topping['name']); ?></div>
                                                    <div class="topping-price">+$<?php echo number_format($topping['price'], 2); ?></div>
                                                </div>
                                                <div class="form-check mt-1">
                                                    <?php 
                                                        // Replace spaces with underscores in the ID to make it valid HTML
                                                        $toppingId = str_replace(' ', '_', $topping['name']);
                                                    ?>
                                                    <input class="form-check-input" type="checkbox" name="toppings[]" 
                                                        id="topping_<?php echo htmlspecialchars($toppingId); ?>" 
                                                        value="<?php echo htmlspecialchars($topping['name']); ?>">
                                                    <label class="form-check-label" for="topping_<?php echo htmlspecialchars($toppingId); ?>">
                                                        Add <?php echo htmlspecialchars($topping['name']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-4">
                                <a href="menu.php" class="btn btn-outline-secondary">Back to Menu</a>
                                <button type="submit" name="add_customized_drink" class="btn btn-primary">
                                    Add to Cart
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Price Calculator -->
                <div class="card mt-3 shadow-sm">
                    <div class="card-body">
                        <div id="price-summary" data-base-price="<?php echo $item_price; ?>">
                            <p class="mb-1">Base Price: <span id="base-price">$<?php echo number_format($item_price, 2); ?></span></p>
                            <p class="mb-1">Toppings: <span id="toppings-price">$0.00</span></p>
                            <hr>
                            <p class="fw-bold mb-0">Total Price: <span id="total-price">$<?php echo number_format($item_price, 2); ?></span></p>
                        </div>
                        
                        <!-- Hidden div to store topping prices -->
                        <div id="topping-prices-data" class="d-none">
                            <?php foreach ($toppings as $topping): ?>
                            <span data-topping="<?php echo $topping["name"]; ?>" data-price="<?php echo $topping["price"]; ?>"></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include "inc/footer.inc.php"; ?>
    
    <!-- Include the external JavaScript file -->
    <script src="js/customize_drink.js"></script>
</body>
</html>

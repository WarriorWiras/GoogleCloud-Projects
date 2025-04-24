<?php
// Start the session
session_start();



// Initialize the cart if it doesn't exist
if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

// Handle adding items to cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_to_cart"])) {
    $newItem = [
        "name" => $_POST["item_name"],
        "ingredients" => $_POST["item_ingredients"],
        "price" => floatval($_POST["item_price"]),
    ];
    
    $_SESSION["cart"][] = $newItem;
    
    // Optional: Display success message
    $success_message = "Added {$_POST["item_name"]} to your cart!";
}

// Sample menu items - in a real application, these would come from a database
$menuItems = [
    [
        "name" => "Classic Milk Tea",
        "ingredients" => "Black tea, milk, tapioca pearls",
        "price" => 4.50,
        "image" => "images/test1.jpg"
    ],
    [
        "name" => "Taro Milk Tea", 
        "ingredients" => "Taro powder, milk, tapioca pearls",
        "price" => 5.00,
        "image" => "images/test2.jpg"
    ],
];
?>

<!DOCTYPE html>
<html lang="en">
<?php include "inc/head.inc.php"; ?>
<body>
    <!-- Include Navigation -->
    <?php include "inc/nav.inc.php"; ?>
    
    <header class="jumbotron text-center text-light bg-dark rounded-0">
        <h1 class="display-4">Bubble Tea Menu</h1>
        <h2>Choose your favorite drinks</h2>
    </header>
    
    <main class="container my-4">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $success_message; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-9">
                <div class="row">
                    <?php foreach ($menuItems as $item): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <img src="<?php echo $item['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                    onerror="this.src='images/default-boba.jpg'">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($item['ingredients']); ?></p>
                                    <p class="card-text text-primary font-weight-bold">
                                        $<?php echo number_format($item['price'], 2); ?>
                                    </p>
                                    <form method="post">
                                        <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($item['name']); ?>">
                                        <input type="hidden" name="item_ingredients" value="<?php echo htmlspecialchars($item['ingredients']); ?>">
                                        <input type="hidden" name="item_price" value="<?php echo $item['price']; ?>">
                                        <button type="submit" name="add_to_cart" class="btn btn-primary btn-block">
                                            Add to Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Your Cart</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($_SESSION["cart"])): ?>
                            <p>Your cart is empty.</p>
                        <?php else: ?>
                            <ul class="list-unstyled">
                                <?php foreach ($_SESSION["cart"] as $cartItem): ?>
                                    <li class="mb-2">
                                        <strong><?php echo htmlspecialchars($cartItem["name"]); ?></strong>
                                        <br>
                                        <small>$<?php echo number_format($cartItem["price"], 2); ?></small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <hr>
                            <p class="mb-0">
                                <strong>Total Items:</strong> <?php echo count($_SESSION["cart"]); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <a href="cart.php" class="btn btn-success btn-block">View Cart</a>
                    </div>
                </div>

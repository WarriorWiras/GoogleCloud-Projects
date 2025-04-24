<?php
// Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
} 
//Check if user is logged in
if (!isset($_SESSION['member_id'])) {
    header("Location: menu.php");
    exit();
}

// Initialize the cart if it doesn't exist
if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

// Handle removing items from cart
if (isset($_POST["remove_item"]) && isset($_POST["item_index"]) && isset($_POST["csrf_token"]) && $_POST["csrf_token"] === $_SESSION["csrf_token"]) {
    $index = (int)$_POST["item_index"];
    if (isset($_SESSION["cart"][$index])) {
        unset($_SESSION["cart"][$index]);
        // Reindex the array
        $_SESSION["cart"] = array_values($_SESSION["cart"]);
    }
}

// Handle quantity adjustments
if (isset($_POST["adjust_quantity"]) && isset($_POST["item_index"]) && isset($_POST["action"]) && isset($_POST["csrf_token"]) && $_POST["csrf_token"] === $_SESSION["csrf_token"]) {
    $index = (int)$_POST["item_index"];
    $action = $_POST["action"];
    
    if (isset($_SESSION["cart"][$index])) {
        // Initialize quantity if it doesn't exist
        if (!isset($_SESSION["cart"][$index]["quantity"])) {
            $_SESSION["cart"][$index]["quantity"] = 1;
        }
        
        if ($action === "increase") {
            // Increase quantity
            $_SESSION["cart"][$index]["quantity"]++;
        } else if ($action === "decrease") {
            // Decrease quantity
            $_SESSION["cart"][$index]["quantity"]--;
            
            // Remove item if quantity reaches 0
            if ($_SESSION["cart"][$index]["quantity"] <= 0) {
                unset($_SESSION["cart"][$index]);
                // Reindex the array
                $_SESSION["cart"] = array_values($_SESSION["cart"]);
            }
        }
    }
}

// Handle clearing the entire cart
if (isset($_POST["clear_cart"]) && isset($_POST["csrf_token"]) && $_POST["csrf_token"] === $_SESSION["csrf_token"]) {
    $_SESSION["cart"] = [];
}

// Generate CSRF token if it doesn't exist
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Ensure all cart items have a quantity
foreach ($_SESSION["cart"] as $index => $item) {
    if (!isset($_SESSION["cart"][$index]["quantity"])) {
        $_SESSION["cart"][$index]["quantity"] = 1;
    }
}

// Get user's reward points
$points = 0;
try {
    $config = parse_ini_file('/var/www/private/db-config.ini');
    $pdo = new PDO("mysql:host={$config['servername']};dbname={$config['dbname']}", $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT points FROM reward_point WHERE member_id = ?");
    $stmt->execute([$_SESSION['member_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $points = $result['points'];
    }
} catch (PDOException $e) {
    // Silently log the error but continue with 0 points
    error_log("Error fetching reward points: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Just My Cup of Tea</title>
    <?php include "inc/head.inc.php"; ?>
    <link rel="stylesheet" href="css/cart.css">
</head>
<body>
    <?php include "inc/nav.inc.php"; ?>
    
    <header class="py-4 bg-custom-purple text-custom-purple text-center">
        <h1>Your Cart</h1>
        <p class="lead">Review your bubble tea selections</p>
    </header>
    
    <main class="container py-4">
        <?php if (count($_SESSION["cart"]) > 0): ?>
            <section class="cart-content">
            <header class="bg-custom-purple text-custom-purple p-3 rounded-top">
            <h2 class="h4 m-0">Your Items</h2>
            </header>
                
                <div class="bg-white p-3 shadow-sm rounded-bottom">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col" class="text-start">Item</th>
                                <th scope="col" class="text-center">Price</th>
                                <th scope="col" class="text-end">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total = 0;
                            foreach ($_SESSION["cart"] as $index => $item): 
                                // Skip items without price
                                if (!isset($item["price"])) continue;
                                
                                $price = $item["price"];
                                $quantity = $item["quantity"] ?? 1;
                                $itemTotal = $price * $quantity;
                                $total += $itemTotal;
                            ?>
                                <tr class="item-row" data-item-index="<?= $index ?>">
                                    <td class="text-start"><?= htmlspecialchars($item["name"] ?? "Unnamed Item") ?></td>
                                    <td class="text-center item-price">$<?= number_format($price, 2) ?></td>
                                    <td class="text-end">
                                        <div class="quantity-controls d-flex align-items-center justify-content-end">
                                            <form method="post" class="d-inline quantity-form">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION["csrf_token"] ?>">
                                                <input type="hidden" name="item_index" value="<?= $index ?>">
                                                <input type="hidden" name="action" value="decrease">
                                                <button type="submit" name="adjust_quantity" class="btn btn-sm btn-quantity-control decrease-btn" 
                                                        aria-label="Decrease quantity of <?= htmlspecialchars($item["name"] ?? "item") ?>">
                                                    −
                                                </button>
                                            </form>
                                            
                                            <span class="quantity-display mx-2"><?= $quantity ?></span>
                                            
                                            <form method="post" class="d-inline quantity-form">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION["csrf_token"] ?>">
                                                <input type="hidden" name="item_index" value="<?= $index ?>">
                                                <input type="hidden" name="action" value="increase">
                                                <button type="submit" name="adjust_quantity" class="btn btn-sm btn-quantity-control increase-btn" 
                                                        aria-label="Increase quantity of <?= htmlspecialchars($item["name"] ?? "item") ?>">
                                                    +
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="details-row">
                                    <td colspan="3" class="text-start">
                                        <p class="small mb-0"><strong>Sugar:</strong> <?= htmlspecialchars($item['sugar_level']) ?></p>
                                        <p class="small mb-0"><strong>Ice:</strong> <?= htmlspecialchars($item['ice_level']) ?></p>
                                        <?php if (!empty($item['toppings'])): ?>
                                            <p class="small mb-0"><strong>Toppings:</strong> <?= htmlspecialchars(implode(', ', $item['toppings'])) ?></p>
                                        <?php else: ?>
                                            <p class="small mb-0"><strong>Toppings:</strong> None</p>
                                        <?php endif; ?>
                                        <p class="subtotal-text text-end item-subtotal"><strong>Subtotal:</strong> $<?= number_format($itemTotal, 2) ?></p>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td class="text-end">Total:</td>
                                <td class="text-center cart-total">$<?= number_format($total, 2) ?></td>
                                <td></td>
                            </tr>
                            <?php if ($points > 0): ?>
                            <tr id="points-discount-row" style="display: none;">
                                <td class="text-end">Points Discount:</td>
                                <td class="text-center points-discount">-$0.00</td>
                                <td></td>
                            </tr>
                            <tr id="final-total-row" style="display: none;">
                                <td class="text-end fw-bold">Final Total:</td>
                                <td class="text-center final-total fw-bold">$<?= number_format($total, 2) ?></td>
                                <td></td>
                            </tr>
                            <?php endif; ?>
                        </tfoot>
                    </table>
                    
                    <div class="d-flex justify-content-between mt-3">
                        <div class="d-flex align-items-center">
                            <form method="post" class="me-2">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION["csrf_token"] ?>">
                                <button type="submit" name="clear_cart" class="btn btn-outline-custom-delete">
                                    Clear Cart
                                </button>
                            </form>
                            <a href="menu.php" class="btn btn-outline-custom-purple">Continue Shopping</a>
                        </div>
                        <div class="checkout-section">
                            <?php if ($points > 0): ?>
                            <div class="points-redemption mb-2">
                                <label class="d-flex align-items-center">
                                    <input type="checkbox" id="redeem-points" class="me-2">
                                    <span>Redeem <?= $points ?> points ($<?= number_format($points * 0.01, 2) ?> value)</span>
                                </label>
                            </div>
                            <?php endif; ?>
                            <form action="Checkout.php" method="POST" id="checkout-form">
                                <input type="hidden" name="order_details" value="<?= htmlspecialchars(json_encode($_SESSION['cart'])) ?>">
                                <input type="hidden" name="points_redeemed" id="points-redeemed-input" value="0">
                                <button type="submit" class="btn btn-custom-purple">Proceed to Checkout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <section class="empty-cart text-center py-5 bg-light rounded">
                <h2 class="mt-3">Your cart is empty</h2>
                <p class="text-muted mb-4">Looks like you haven't added any bubble tea to your cart yet.</p>
                <a href="menu.php" class="btn btn-custom-purple">Start Shopping</a>
            </section>
        <?php endif; ?>
    </main>
    
    <?php include "inc/footer.inc.php"; ?>
    
    <!-- Add script tag to load cart.js -->
    <script src="js/cart.js"></script>
    <script>
        // Initialize variables for point redemption
        const availablePoints = <?= $points ?>;
        const pointsValue = availablePoints * 0.01;
        const originalTotal = <?= $total ?>;
    </script>
</body>
</html>
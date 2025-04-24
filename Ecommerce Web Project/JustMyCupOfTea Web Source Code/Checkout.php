<?php
// Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
} 
if ($_SERVER["REQUEST_METHOD"] == "POST") {
     $order_details = $_POST['order_details'] ?? "No Toppings selected";
     // Get points redeemed from the form submission
     $points_redeemed = intval($_POST['points_redeemed'] ?? 0);
 } else {
     header("Location: index.php"); 
 }

$input = json_decode(file_get_contents("php://input"), true);
$phone = $input['phone'] ?? '';
$_SESSION['phone'] = $phone;
$apiKeys = parse_ini_file('/var/www/private/api-keys.ini'); //private key location
 
// Process the cart contents to create a summary
$totalAmount = 0;
$orderSummary = "";
 
foreach ($_SESSION["cart"] as $index => $item) {
    if (isset($item["price"])) {
        $quantity = $item["quantity"] ?? 1;
        $totalAmount += $item["price"] * $quantity;
        $orderSummary .= $item["name"] . " x" . $quantity . ", ";
    }
}

// Calculate points discount (1 point = $0.01)
$pointsDiscount = 0;
if ($points_redeemed > 0) {
    // Store the points being redeemed in the session for use in checkout-session.php
    $_SESSION['points_redeemed'] = $points_redeemed;
    $pointsDiscount = $points_redeemed * 0.01;
    // Ensure discount doesn't exceed total amount
    $pointsDiscount = min($pointsDiscount, $totalAmount);
}

// Calculate final total after discount
$finalTotal = $totalAmount - $pointsDiscount;
 
// Remove trailing comma
$orderSummary = rtrim($orderSummary, ", ");
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Checkout - Just My Cup of Tea</title>
    <?php include "inc/head.inc.php"; ?>
    <link rel="stylesheet" href="css/checkout.css">
</head>
<body>
    <?php include "inc/nav.inc.php"; ?>
    
    <header class="py-4 bg-custom-purple text-custom-purple text-center">
        <h1>Order Confirmation</h1>
        <p class="lead">Review your order before checkout</p>
    </header>
    
    <main class="container py-4">
        <section class="cart-content order-details">
            <header class="bg-custom-purple text-custom-purple p-3 rounded-top">
                <h2 class="h4 m-0">Order Details</h2>
            </header>
            
            <div class="bg-white p-3 shadow-sm rounded-bottom order-summary-card">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col" class="text-start">Item</th>
                            <th scope="col" class="text-center">Quantity</th>
                            <th scope="col" class="text-end">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION["cart"] as $item): 
                            $quantity = $item["quantity"] ?? 1;
                            $price = $item["price"] ?? 0;
                            $subtotal = $price * $quantity;
                        ?>
                        <tr>
                            <td class="text-start"><?= htmlspecialchars($item["name"] ?? "Unnamed Item") ?></td>
                            <td class="text-center"><?= $quantity ?></td>
                            <td class="text-end">$<?= number_format($price, 2) ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-start">
                                <p class="small mb-0"><strong>Sugar:</strong> <?= htmlspecialchars($item['sugar_level'] ?? 'N/A') ?></p>
                                <p class="small mb-0"><strong>Ice:</strong> <?= htmlspecialchars($item['ice_level'] ?? 'N/A') ?></p>
                                <?php if (!empty($item['toppings'])): ?>
                                    <p class="small mb-0"><strong>Toppings:</strong> <?= htmlspecialchars(implode(', ', $item['toppings'])) ?></p>
                                <?php else: ?>
                                    <p class="small mb-0"><strong>Toppings:</strong> None</p>
                                <?php endif; ?>
                                <p class="subtotal-text text-end"><strong>Subtotal:</strong> $<?= number_format($subtotal, 2) ?></p>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td class="text-end" colspan="2">Total:</td>
                            <td class="text-end">$<?= number_format($totalAmount, 2) ?></td>
                        </tr>
                        <?php if ($pointsDiscount > 0): ?>
                        <tr>
                            <td class="text-end" colspan="2">Points Discount (<?= $points_redeemed ?> points):</td>
                            <td class="text-end text-success">-$<?= number_format($pointsDiscount, 2) ?></td>
                        </tr>
                        <tr class="fw-bold">
                            <td class="text-end" colspan="2">Final Total:</td>
                            <td class="text-end">$<?= number_format($finalTotal, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                    </tfoot>
                </table>
                
                <div class="d-flex justify-content-between mt-3">
                    <a href="cart.php" class="btn btn-outline-custom-purple">Back to Cart</a>
                    <form id="checkout-form">
                        <button type="button" id="checkout-button" class="btn btn-custom-purple">Proceed to Payment</button>
                    </form>
                </div>
            </div>
        </section>
    </main>
    
    <?php include "inc/footer.inc.php"; ?>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
    const stripe = Stripe("<?= $apiKeys['stripe_public_key']; ?>");
    document.getElementById("checkout-button").addEventListener("click", function () {
        fetch("checkout-session.php", {
            method: "POST",
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                pointsRedeemed: <?= $points_redeemed ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.id) {
                // Redirect to Stripe Checkout if session ID is received
                stripe.redirectToCheckout({ sessionId: data.id });
            } else {
                alert("Error: " + data.error);
            }
        });
    });
    </script>
</body>
</html>

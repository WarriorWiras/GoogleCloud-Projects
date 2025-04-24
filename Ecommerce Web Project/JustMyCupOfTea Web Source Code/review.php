<?php
// Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['member_id'])) {
    // Redirect to menu page if not logged in
    header("Location: menu.php");
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: purchase_history.php");
    exit();
}

$order_id = $_GET['order_id'];
$member_id = $_SESSION['member_id'];
$success_message = "";
$error_message = "";

// Database connection
try {
    $config = parse_ini_file('/var/www/private/db-config.ini');
    $pdo = new PDO("mysql:host={$config['servername']};dbname={$config['dbname']}", $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verify that this order belongs to the logged-in user
    $stmt = $pdo->prepare("
        SELECT o.order_id, o.status, o.points_redeemed
        FROM Justmycupoftea_orders o 
        WHERE o.order_id = ? AND o.member_id = ?
    ");
    $stmt->execute([$order_id, $member_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if order exists, belongs to user, and is paid
    if (!$order || $order['status'] !== 'paid') {
        header("Location: purchase_history.php");
        exit();
    }

    // Check if order has already been reviewed
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM feedback
        WHERE order_id = ?
    ");
    $stmt->execute([$order_id]);
    if ($stmt->fetchColumn() > 0) {
        // Order already reviewed, redirect back
        header("Location: purchase_history.php");
        exit();
    }

    // Get order items to display in review form
    $stmt = $pdo->prepare("
        SELECT oi.*, m.name, oi.item_id
        FROM Justmycupoftea_order_items oi
        JOIN Justmycupoftea_menu m ON oi.drink_id = m.drink_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['rating']) && isset($_POST['comment'])) {
            $rating = $_POST['rating'];
            $comment = $_POST['comment'];
            
            // Insert review into database
            $stmt = $pdo->prepare("
                INSERT INTO feedback (member_id, order_id, rating, comment, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$member_id, $order_id, $rating, $comment]);
            
            // Get the total amount spent on this order to calculate bonus points
            $stmt = $pdo->prepare("
                SELECT total_amount 
                FROM Justmycupoftea_orders 
                WHERE order_id = ?
            ");
            $stmt->execute([$order_id]);
            $orderTotal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($orderTotal && isset($orderTotal['total_amount'])) {
                // Calculate bonus points (floor of total amount)
                $bonusPoints = floor($orderTotal['total_amount']);
                
                // Check if user already has points
                $stmt = $pdo->prepare("
                    SELECT points 
                    FROM reward_point 
                    WHERE member_id = ?
                ");
                $stmt->execute([$member_id]);
                $pointsRecord = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($pointsRecord) {
                    // Update existing points
                    $stmt = $pdo->prepare("
                        UPDATE reward_point 
                        SET points = points + ?, last_updated = NOW() 
                        WHERE member_id = ?
                    ");
                    $stmt->execute([$bonusPoints, $member_id]);
                } else {
                    // Create new points record
                    $stmt = $pdo->prepare("
                        INSERT INTO reward_point (member_id, points, last_updated) 
                        VALUES (?, ?, NOW())
                    ");
                    $stmt->execute([$member_id, $bonusPoints]);
                }
            }
            
            $success_message = "Thank you for your review! You earned $bonusPoints bonus points!";
        } else {
            $error_message = "Please provide both a rating and comment.";
        }
    }

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include "inc/head.inc.php"; ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Order - Just My Cup of Tea</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="css/review.css">
</head>
<body>
    <?php include "inc/nav.inc.php"; ?>
    
    <header class="py-4 bg-custom-purple text-custom-purple text-center">
        <h1>Rate Your Order</h1>
        <p class="lead">Order #<?= $order_id ?></p>
    </header>
    
    <main class="container py-4">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
            <div class="points-earned mb-4">
                <p>Your review has earned you bonus points! These points have been added to your account.</p>
            </div>
            <div class="text-center mt-3 mb-5">
                <a href="purchase_history.php" class="btn btn-custom-purple">Back to Purchase History</a>
            </div>
        <?php else: ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?= $error_message ?></div>
            <?php endif; ?>
            
            <section class="order-details mb-4">
                <header class="bg-custom-purple text-custom-purple p-3 rounded-top d-flex justify-content-between align-items-center">
                    <h2 class="h4 m-0">Order Summary</h2>
                </header>
                
                <div class="bg-white p-3 shadow-sm rounded-bottom order-summary-card">
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col" class="text-start">Item</th>
                                <th scope="col" class="text-center">Quantity</th>
                                <th scope="col" class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_amount = 0;
                            foreach ($items as $item): 
                                $subtotal = $item['price_at_purchase'] * $item['quantity'];
                                $total_amount += $subtotal;
                                
                                // Get toppings for this item
                                $toppingsStmt = $pdo->prepare("
                                    SELECT t.name
                                    FROM order_toppings ot
                                    JOIN toppings t ON ot.topping_id = t.topping_id
                                    WHERE ot.item_id = ?
                                ");
                                $toppingsStmt->execute([$item['item_id']]);
                                $toppings = $toppingsStmt->fetchAll(PDO::FETCH_COLUMN);
                            ?>
                            <tr>
                                <td class="text-start">
                                    <div><?= htmlspecialchars($item['name']) ?></div>
                                    <small class="text-muted">
                                        Toppings: <?= !empty($toppings) ? htmlspecialchars(implode(', ', $toppings)) : 'None' ?>
                                    </small>
                                </td>
                                <td class="text-center"><?= $item['quantity'] ?></td>
                                <td class="text-end">$<?= number_format($subtotal, 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <?php 
                            // Calculate discount if points were redeemed
                            if ($order['points_redeemed'] > 0):
                                $discount = $order['points_redeemed'] * 0.01; // 1 point = $0.01
                            ?>
                            <tr>
                                <td class="text-end" colspan="2">Discount (<?= $order['points_redeemed'] ?> points):</td>
                                <td class="text-end">-$<?= number_format($discount, 2) ?></td>
                            </tr>
                            <?php
                                // Adjust total amount to reflect the discount
                                $total_amount -= $discount;
                            endif;
                            ?>
                            <tr class="fw-bold">
                                <td class="text-end" colspan="2">Total:</td>
                                <td class="text-end">$<?= number_format($total_amount, 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
            
            <div class="review-form">
                <h3 class="mb-4">Write Your Review</h3>
                <form method="post">
                    <div class="mb-4">
                        <label class="form-label">Rate your experience:</label>
                        <div class="rating">
                            <input type="radio" id="star5" name="rating" value="5" required />
                            <label for="star5" title="5 stars">★</label>
                            <input type="radio" id="star4" name="rating" value="4" />
                            <label for="star4" title="4 stars">★</label>
                            <input type="radio" id="star3" name="rating" value="3" />
                            <label for="star3" title="3 stars">★</label>
                            <input type="radio" id="star2" name="rating" value="2" />
                            <label for="star2" title="2 stars">★</label>
                            <input type="radio" id="star1" name="rating" value="1" />
                            <label for="star1" title="1 star">★</label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="comment" class="form-label">Your comments:</label>
                        <textarea class="form-control" id="comment" name="comment" rows="4" required placeholder="Tell us about your experience..."></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="purchase_history.php" class="btn btn-outline-custom-purple">Cancel</a>
                        <button type="submit" class="btn btn-custom-purple">Submit Review</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include "inc/footer.inc.php"; ?>
</body>
</html>

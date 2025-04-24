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

// Database connection
try {
    $config = parse_ini_file('/var/www/private/db-config.ini');
    $pdo = new PDO("mysql:host={$config['servername']};dbname={$config['dbname']}", $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $member_id = $_SESSION['member_id'];

    // Get user's orders
    $stmt = $pdo->prepare("
        SELECT o.order_id, o.total_amount, o.created_at AS order_date, o.status, o.points_redeemed 
        FROM Justmycupoftea_orders o 
        WHERE o.member_id = ? 
        ORDER BY o.order_id DESC
    ");
    $stmt->execute([$member_id]);

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check which orders have been reviewed
    foreach ($orders as &$order) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM feedback 
            WHERE order_id = ?
        ");
        $stmt->execute([$order['order_id']]);
        $order['has_review'] = ($stmt->fetchColumn() > 0);
    }
    unset($order); // Break the reference

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "inc/head.inc.php"; ?>
    <title>Purchase History - Just My Cup of Tea</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/purchase_history.css">
</head>
<body>
    <?php include "inc/nav.inc.php"; ?>
    
    <header class="py-4 bg-custom-purple text-custom-purple text-center">
        <h1>Purchase History</h1>
        <p class="lead">View your past orders</p>
    </header>
    
    <main class="container py-4">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>
        
        <?php if (empty($orders)): ?>
            <div class="no-orders-container">
                <h2>You haven't placed any orders yet.</h2>
                <p>Explore our menu and place your first order today!</p>
                <a href="index.php" class="btn btn-custom-purple">Browse Menu</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <section class="order-details mb-4">
                    <header class="bg-custom-purple text-custom-purple p-3 rounded-top d-flex justify-content-between align-items-center">
                        <h2 class="h4 m-0">Order #<?= $order['order_id'] ?></h2>
                        <div>
                            <span class="badge <?= $order['status'] === 'paid' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                            <?php if($order['status'] === 'paid' && !$order['has_review']): ?>
                                <a href="review.php?order_id=<?= $order['order_id'] ?>" class="btn btn-sm btn-review ms-2">Rate</a>
                            <?php elseif($order['status'] === 'paid' && $order['has_review']): ?>
                                <span class="badge bg-info ms-2">Rated</span>
                            <?php endif; ?>
                            <span class="order-date ms-2"><?= date('M d, Y', strtotime($order['order_date'])) ?></span>
                        </div>
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
                                <?php
                                // Get order items
                                $stmt = $pdo->prepare("
                                    SELECT oi.*, m.name, oi.item_id
                                    FROM Justmycupoftea_order_items oi
                                    JOIN Justmycupoftea_menu m ON oi.drink_id = m.drink_id
                                    WHERE oi.order_id = ?
                                ");
                                $stmt->execute([$order['order_id']]);
                                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($items as $item): 
                                    $subtotal = $item['price_at_purchase'] * $item['quantity'];

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
                                    <td class="text-end">$<?= number_format($item['price_at_purchase'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end">
                                        <p class="subtotal-text"><strong>Subtotal:</strong> $<?= number_format($subtotal, 2) ?></p>
                                    </td>
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
                                <?php endif; ?>
                                <tr class="fw-bold">
                                    <td class="text-end" colspan="2">Total:</td>
                                    <td class="text-end">$<?= number_format($order['total_amount'], 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    
    <?php include "inc/footer.inc.php"; ?>
</body>
</html>

<?php
require 'vendor/autoload.php';
$apiKeys = parse_ini_file('/var/www/private/api-keys.ini');
\Stripe\Stripe::setApiKey($apiKeys['stripe_secret_key']); //Secret key


session_start();
$cart = $_SESSION['cart'];  // cart = [ ['drink_id' => 1, 'quantity' => 2], ... ]

// Get points redeemed from session
$points_redeemed = intval($_SESSION['points_redeemed'] ?? 0);
$points_discount = 0;

try {
    $pdo = new PDO("mysql:host=localhost;dbname=Justmycupoftea", "<usernmae>", "<password>");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $total = 0;
    $line_items = [];

    foreach ($cart as $item) {
        // Get drink details from the menu
        $stmt = $pdo->prepare("SELECT name, price FROM Justmycupoftea_menu WHERE drink_id = ?");
        $stmt->execute([$item['drink_id']]);
        $drink = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$drink || !isset($drink['name'], $drink['price'])) {
            throw new Exception("Drink not found or missing fields for ID: " . $item['drink_id']);
        }

        // Base price of the drink
        $unitAmount = (int)($drink['price'] * 100);  // Convert to cents

        // Get toppings that the customer customized
        $toppings_price = 0;
        if (!empty($item['toppings'])) {
            // Add topping prices (adjust this to match how you define topping prices)
            foreach ($item['toppings'] as $topping) {
                switch ($topping) {
                    case 'Pearl':
                        $toppings_price += 0.50;
                        break;
                    case 'White Pearl':
                        $toppings_price += 0.50;
                        break;
                    case 'Aloe Vera':
                        $toppings_price += 0.75;
                        break;
                    case 'Grass Jelly':
                        $toppings_price += 0.75;
                        break;
                    case 'Red Bean':
                        $toppings_price += 1.00;
                        break;
                    case 'Pudding':
                        $toppings_price += 1.00;
                        break;
                    default:
                        break;
                }
            }
        }

        // Total price including toppings
        $unitAmount = (int)(($drink['price'] + $toppings_price) * 100);  // Add toppings to the base price

        // Add the drink item to the line items
        $line_items[] = [
            'price_data' => [
                'currency' => 'sgd',
                'unit_amount' => $unitAmount,
                'product_data' => [
                    'name' => $drink['name'] . " (Toppings: " . implode(", ", $item['toppings']) . ")"
                ]
            ],
            'quantity' => $item['quantity']
        ];

        // Add the price to the total
        $total += ($drink['price'] + $toppings_price) * $item['quantity'];
    }

    // Calculate points discount (1 point = $0.01)
    if ($points_redeemed > 0) {
        $points_discount = $points_redeemed * 0.01;
        // Ensure discount doesn't exceed total amount
        $points_discount = min($points_discount, $total);
    }

    // Calculate final total after discount
    $final_total = $total - $points_discount;

    // Instead of adding the discount as a separate line item, use Stripe's coupon system
    if ($points_discount > 0) {
        try {
            // Create a coupon with a fixed amount off
            $coupon = \Stripe\Coupon::create([
                'amount_off' => (int)($points_discount * 100), // Convert to cents
                'currency' => 'sgd',
                'name' => 'Loyalty Points Discount (' . $points_redeemed . ' points)',
                'duration' => 'once'
            ]);
            
            // Create Stripe Checkout session with the coupon
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'line_items' => $line_items,
                'discounts' => [['coupon' => $coupon->id]],
                'success_url' => 'http://<ip_address>/checkout_success.php?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'http://<ip_address>/Checkout.php',
            ]);
        } catch (\Exception $e) {
            // If coupon creation fails, fall back to a note item (but without the positive price)
            $line_items[] = [
                'price_data' => [
                    'currency' => 'sgd',
                    'unit_amount' => 0, // Zero cost item
                    'product_data' => [
                        'name' => 'Loyalty Points Discount: -$' . number_format($points_discount, 2) . ' (' . $points_redeemed . ' points)'
                    ]
                ],
                'quantity' => 1
            ];
            
            // Create checkout session with adjusted total
            $adjusted_line_items = [];
            
            // Adjust the first item's price to account for the discount
            if (!empty($line_items) && $total > 0) {
                $first_item = $line_items[0];
                $first_item_total = $first_item['price_data']['unit_amount'] * $first_item['quantity'];
                
                if ($first_item_total > $points_discount * 100) {
                    // Subtract discount from first item
                    $first_item['price_data']['unit_amount'] -= intval(($points_discount * 100) / $first_item['quantity']);
                    $adjusted_line_items[] = $first_item;
                    
                    // Add remaining items unchanged
                    for ($i = 1; $i < count($line_items); $i++) {
                        $adjusted_line_items[] = $line_items[$i];
                    }
                } else {
                    // Discount is larger than first item, distribute across multiple items
                    $remaining_discount = $points_discount * 100;
                    
                    foreach ($line_items as $item) {
                        $item_total = $item['price_data']['unit_amount'] * $item['quantity'];
                        
                        if ($remaining_discount > 0 && $item_total > 0) {
                            $discount_for_item = min($remaining_discount, $item_total);
                            $item['price_data']['unit_amount'] -= intval($discount_for_item / $item['quantity']);
                            $remaining_discount -= $discount_for_item;
                        }
                        
                        $adjusted_line_items[] = $item;
                    }
                }
            } else {
                $adjusted_line_items = $line_items;
            }
            
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'line_items' => $adjusted_line_items,
                'success_url' => 'http://<ip_address>/checkout_success.php?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'http://<ip_address>/Checkout.php',
            ]);
        }
    } else {
        // No discount, create regular checkout session
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => $line_items,
            'success_url' => 'http://<ip_address>/checkout_success.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'http://<ip_address>/Checkout.php',
        ]);
    }

    // Insert into orders table
    $stmt = $pdo->prepare("INSERT INTO Justmycupoftea_orders (member_id, total_amount, payment_intent_id, points_redeemed) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['member_id'], $final_total, $session->payment_intent, $points_redeemed]);
    $order_id = $pdo->lastInsertId();

    // Insert each drink into order_items with the correct price (base price + toppings price)
    foreach ($cart as $item) {
        // Get the price from the cart item (this already includes toppings)
        $price_at_purchase = $item['price'];  // This is the price already calculated (base price + toppings)

        // Insert into order_items
        $stmt = $pdo->prepare("INSERT INTO Justmycupoftea_order_items (order_id, drink_id, quantity, price_at_purchase)
                               VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $item['drink_id'], $item['quantity'], $price_at_purchase]);
        
        // Get the item_id for the recently inserted order item
        $item_id = $pdo->lastInsertId();
        
        // If there are toppings, add them to the order_toppings table
        if (!empty($item['toppings'])) {
            foreach ($item['toppings'] as $topping_name) {
                // Get the topping_id for the topping name
                $stmt = $pdo->prepare("SELECT topping_id FROM toppings WHERE name = ?");
                $stmt->execute([$topping_name]);
                $topping = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($topping) {
                    // Insert into order_toppings table
                    $stmt = $pdo->prepare("INSERT INTO order_toppings (topping_id, item_id) VALUES (?, ?)");
                    $stmt->execute([$topping['topping_id'], $item_id]);
                }
            }
        }
    }

    // If points were redeemed, update the user's points balance
    if ($points_redeemed > 0 && isset($_SESSION['member_id'])) {
        $stmt = $pdo->prepare("UPDATE reward_point SET points = points - ? WHERE member_id = ?");
        $stmt->execute([$points_redeemed, $_SESSION['member_id']]);
        
        // Reset the points_redeemed in session to prevent double-redemption
        $_SESSION['points_redeemed'] = 0;
    }

    echo json_encode(['id' => $session->id]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

?>

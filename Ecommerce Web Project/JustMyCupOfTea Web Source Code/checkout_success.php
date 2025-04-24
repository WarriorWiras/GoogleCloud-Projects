<?php
// Start the session
session_start();

// Ensure we have a session_id in the URL
$session_id = $_GET['session_id'] ?? null;

if (!$session_id) {
    die("No session ID provided.");
}

require __DIR__ . '/vendor/autoload.php'; 
use Twilio\Rest\Client;


$apiKeys = parse_ini_file('/var/www/private/api-keys.ini');
\Stripe\Stripe::setApiKey($apiKeys['stripe_secret_key']);


// Twilio credentials
$account_sid = $apiKeys['twilio_account_sid'];
$auth_token = $apiKeys['twilio_auth_token'];
$twilio_number = $apiKeys['twilio_phone_number'];

try {
    // Retrieve the session
    $session = \Stripe\Checkout\Session::retrieve($session_id);
    $paymentIntentId = $session->payment_intent;

    // Connect to DB and update order
    $config = parse_ini_file('/var/www/private/db-config.ini');
    $pdo = new PDO("mysql:host={$config['servername']};dbname={$config['dbname']}", $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get the order details to calculate points
    $stmt = $pdo->prepare("
        SELECT total_amount 
        FROM Justmycupoftea_orders 
        WHERE payment_intent_id IS NULL AND member_id = ? 
        ORDER BY order_id DESC 
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['member_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Update order status
    $stmt = $pdo->prepare("
        UPDATE Justmycupoftea_orders 
        SET status = 'paid', payment_intent_id = ? 
        WHERE payment_intent_id IS NULL AND member_id = ?
    ");
    $stmt->execute([$paymentIntentId, $_SESSION['member_id']]);

    // Calculate points (floor of total amount)
    if ($order && isset($order['total_amount'])) {
        $pointsEarned = floor($order['total_amount']);
        
        // Check if user already has points
        $stmt = $pdo->prepare("
            SELECT points 
            FROM reward_point 
            WHERE member_id = ?
        ");
        $stmt->execute([$_SESSION['member_id']]);
        $pointsRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pointsRecord) {
            // Update existing points
            $stmt = $pdo->prepare("
                UPDATE reward_point 
                SET points = points + ?, last_updated = NOW() 
                WHERE member_id = ?
            ");
            $stmt->execute([$pointsEarned, $_SESSION['member_id']]);
        } else {
            // Create new points record
            $stmt = $pdo->prepare("
                INSERT INTO reward_point (member_id, points, last_updated) 
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$_SESSION['member_id'], $pointsEarned]);
        }
    }

    // Create a temporary copy of the cart for SMS function before clearing it
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $_SESSION['tempcart'] = $_SESSION['cart'];
    }
    
    // Clear the cart after successful payment
    if (isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

} catch (Exception $e) {
    // Log the full error message
    error_log("Stripe error: " . $e->getMessage());
    
    // Output the raw error message to help with debugging
    echo "Stripe error: " . $e->getMessage();
    
    die("Something went wrong. Please contact support.");
}

// Handle SMS if phone number is provided and valid
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['phone'])) {
    $phoneInput = trim($_POST['phone'] ?? '');

    if (!empty($phoneInput) && preg_match('/^[0-9]{8}$/', $phoneInput)) {
        // If phone number is valid, format it with the country code
        $phone = "+65" . $phoneInput;  // Assuming country code for Singapore

        // Generate order details dynamically based on the temporary cart
        $order_details = ""; // Initialize the string before the loop

        foreach ($_SESSION["tempcart"] as $index => $item) {
            // Add item details to the string, no period at the end
            if ($index > 0) {
                $order_details .= "\n";  // Add a newline only between items
            }

            // Ensure that sugar_level is correctly formatted as a percentage with the label
            $sugar_level = isset($item['sugar_level']) ? $item['sugar_level'] . "" : "No sweetness level specified";
    
            // Ice level
            $ice_level = isset($item['ice_level']) ? $item['ice_level'] : "No ice level specified";

            // Get toppings, or display 'None' if no toppings selected
            $toppings = isset($item['toppings']) && !empty($item['toppings']) ? implode(", ", $item['toppings']) : "None";

            // Add the drink details along with the toppings
            $order_details .= "{$item['name']}\n";
            $order_details .= "Sweetness: {$sugar_level}\n";
            $order_details .= "Ice Level: {$ice_level}\n";
            $order_details .= "Toppings: {$toppings}\n";
            $order_details .= "Price: $" . number_format($item["price"], 2);
        }

        // Use session details or cart data if necessary
        $message = "Your bubble tea order is confirmed!\n\n$order_details\n\nIt will be delivered soon!";

        // Create a Twilio client
        $client = new Client($account_sid, $auth_token);

        try {
            // Send SMS
            $client->messages->create($phone, [
                'from' => $twilio_number,
                'body' => $message
            ]);
            echo "SMS sent successfully!";
            
            // Clear the temporary cart after SMS is sent
            unset($_SESSION['tempcart']);
            
        } catch (Exception $e) {
            echo "Error sending SMS: " . $e->getMessage();
        }

        // Redirect to the homepage after SMS is sent
        header("Location: index.php");
        exit();
    } else {
        // If the phone number is invalid, show an error message
        echo "Invalid phone number format. Please enter a valid 8-digit number.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Order Received</title>
    <link rel="stylesheet" href="css/checkout_success.css">
    <script>
        // Validate phone number before submission
        function validatePhoneNumber() {
            var phoneInput = document.getElementById("phone").value;
            var regex = /^[0-9]{8}$/;  // 8-digit number validation

            if (regex.test(phoneInput)) {
                return true;  // Allow form submission
            } else {
                alert("Please enter a valid 8-digit phone number.");
                return false;  // Prevent form submission
            }
        }
    </script>
</head>
<body>
    <h1>Order received!</h1>
    <?php if (isset($pointsEarned) && $pointsEarned > 0): ?>
        <div class="points-earned">
            <p>Congratulations! You earned <strong><?= $pointsEarned ?> reward points</strong> with this purchase.</p>
        </div>
    <?php endif; ?>
    <p>Your payment was successful. Would you like to receive an SMS confirmation?</p>

    <!-- Phone Number Input Form -->
    <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" onsubmit="return validatePhoneNumber()">
        <h3>Enter Your Phone Number (optional for SMS confirmation):</h3>
        <input type="text" name="phone" id="phone" placeholder="Enter your number" maxlength="8" inputmode="numeric">
        <button type="submit" class="btn btn-success">Submit</button>
    </form>

    <a href="index.php" class="btn btn-primary">Go back to JustMyCupOfTea!</a>
</body>
</html>

<?php
require __DIR__ . '/vendor/autoload.php'; 
use Twilio\Rest\Client;
$apiKeys = parse_ini_file('/var/www/private/api-keys.ini');

$twilio_sid = $apiKeys['twilio_account_sid'];
$twilio_token = $apiKeys['twilio_auth_token'];
$twilio_number = $apiKeys['twilio_phone_number'];

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phoneInput = trim($_POST['phone'] ?? '');

    if (!empty($phoneInput)) {
        if (preg_match('/^[0-9]{8}$/', $phoneInput)) {
            $phone = "+65" . $phoneInput;
            $order_details = $_POST['order_details'] ?? "Milk Tea";

            $client = new Client($account_sid, $auth_token);
            $message = "Your bubble tea order is confirmed! Drink: $order_details. It will be delivered soon!";

            try {
                $client->messages->create($phone, [
                    'from' => $twilio_number,
                    'body' => $message
                ]);
                echo "SMS sent successfully!";
            } catch (Exception $e) {
                echo "Error sending SMS: " . $e->getMessage();
            }
        } else {
            echo "Invalid phone number format.";
        }
    } else {
        // No phone number provided — skip SMS and proceed to homepage
        echo "No phone number provided — skipping SMS.";
    }

    // Redirect back to homepage
    header("Location: index.php");
    exit();
} else {
    echo "Invalid request.";
}

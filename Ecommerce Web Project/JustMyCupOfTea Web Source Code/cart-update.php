<?php
// Start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize response array
$response = [
    'success' => false,
    'message' => 'Invalid request',
    'quantity' => 0,
    'itemTotal' => 0,
    'total' => 0,
    'removed' => false,
    'cartCount' => 0,
    'cartEmpty' => false,
    'newIndexes' => [] // Array to track new indexes
];

// Check if user is logged in
if (!isset($_SESSION["email"])) {
    $response['message'] = 'User not logged in';
    echo json_encode($response);
    exit();
}

// Check for CSRF token and required parameters
if (isset($_POST["adjust_quantity"]) && 
    isset($_POST["item_index"]) && 
    isset($_POST["action"]) && 
    isset($_POST["csrf_token"]) && 
    $_POST["csrf_token"] === $_SESSION["csrf_token"]) {
    
    $index = (int)$_POST["item_index"];
    $action = $_POST["action"];
    
    // Store the original item name if it exists (for debugging)
    $originalItemName = $_SESSION["cart"][$index]["name"] ?? "Unknown item";
    
    // Check if the item exists in the cart
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
                $response['removed'] = true;
                
                // Create a map of new indexes for all remaining items
                $response['newIndexes'] = array_keys($_SESSION["cart"]);
            }
        }
        
        // Calculate new total
        $total = 0;
        foreach ($_SESSION["cart"] as $item) {
            if (isset($item["price"])) {
                $qty = $item["quantity"] ?? 1;
                $total += $item["price"] * $qty;
            }
        }
        
        // Set response data
        $response['success'] = true;
        $response['message'] = 'Cart updated successfully';
        $response['total'] = $total;
        $response['cartCount'] = count($_SESSION["cart"]);
        $response['cartEmpty'] = count($_SESSION["cart"]) === 0;
        
        // If the item wasn't removed, include its specific details
        if (!$response['removed'] && isset($_SESSION["cart"][$index])) {
            $item = $_SESSION["cart"][$index];
            $response['quantity'] = $item["quantity"];
            $response['itemTotal'] = $item["price"] * $item["quantity"];
        }
    } else {
        $response['message'] = "Item not found in cart: Index $index, Original item: $originalItemName";
    }
} else {
    $response['message'] = 'Invalid request parameters or CSRF token';
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>

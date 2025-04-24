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

$member_id = $_SESSION['member_id'];
$points = 0; // Default to 0 points
$last_updated = null;

// Database connection
try {
    $config = parse_ini_file('/var/www/private/db-config.ini');
    $pdo = new PDO("mysql:host={$config['servername']};dbname={$config['dbname']}", $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get user's points
    $stmt = $pdo->prepare("
        SELECT points, last_updated 
        FROM reward_point 
        WHERE member_id = ?
    ");
    $stmt->execute([$member_id]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $points = $result['points'];
        $last_updated = $result['last_updated'];
    }
    // If no result, points remains at 0

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reward Points - Just My Cup of Tea</title>
    <?php include "inc/head.inc.php"; ?>
    <link rel="stylesheet" href="css/points.css">
</head>
<body>
    <?php include "inc/nav.inc.php"; ?>
    
    <header class="py-4 bg-custom-purple text-custom-purple text-center">
        <h1>My Reward Points</h1>
        <p class="lead">Earn points with every purchase</p>
    </header>
    
    <main class="container py-4">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>
        
        <div class="points-container">
            <div class="points-card">
                <div class="points-header">
                    <h2>Available Points</h2>
                </div>
                <div class="points-body">
                    <div class="points-value"><?= number_format($points) ?></div>
                    <?php if ($last_updated): ?>
                        <div class="points-updated">Last updated: <?= date('M d, Y', strtotime($last_updated)) ?></div>
                    <?php endif; ?>
                </div>
                <div class="points-footer">
                    <p>Earn 1 point for every $1 spent on our products.</p>
                    <p>Use your points for discounts on future purchases!</p>
                </div>
            </div>
            
            <div class="points-info">
                <h3>How to Earn Points</h3>
                <ul>
                    <li>Earn 1 point for every $1 spent on drinks</li>
                    <li>Get bonus points for rating orders</li>
                </ul>
                
                <h3>How to Use Points</h3>
                <ul>
                    <li>1 point = $0.01 off your next order</li>
                    <li>100 points = $1 off your next order</li>
                </ul>
            </div>
        </div>
    </main>
    
    <?php include "inc/footer.inc.php"; ?>
</body>
</html>

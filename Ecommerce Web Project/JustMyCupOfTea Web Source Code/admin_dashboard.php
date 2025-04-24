<?php
session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: index.php");
    exit();
}

// Connect to database
$config = parse_ini_file('/var/www/private/db-config.ini');
$conn = new mysqli($config['servername'], $config['username'], $config['password'], $config['dbname']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch order metrics
$totalOrders = $conn->query("SELECT COUNT(*) FROM Justmycupoftea_orders")->fetch_row()[0];
$totalSales = $conn->query("SELECT SUM(total_amount) FROM Justmycupoftea_orders")->fetch_row()[0];
$averageOrderValue = $conn->query("SELECT AVG(total_amount) FROM Justmycupoftea_orders")->fetch_row()[0];

// Pagination & Search (Customers)
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $_GET['search'] : '';

$searchQuery = '';
if (!empty($search)) {
    $searchQuery = "WHERE email LIKE '%" . $conn->real_escape_string($search) . "%'";
}

// Fetch customers with pagination and search
$result = $conn->query("SELECT * FROM Justmycupoftea_members $searchQuery LIMIT $start, $limit");
$customers = $result->fetch_all(MYSQLI_ASSOC);

// Total customers for pagination with search
$totalCustomers = $conn->query("SELECT COUNT(*) FROM Justmycupoftea_members $searchQuery")->fetch_row()[0];
$totalPages = ceil($totalCustomers / $limit);

// Pagination & Search (Orders)
$orderSearch = isset($_GET['orderSearch']) ? $_GET['orderSearch'] : '';
$orderSearchQuery = '';
if (!empty($orderSearch)) {
    $orderSearchQuery = "WHERE order_id = '" . $conn->real_escape_string($orderSearch) . "'";
}

$orderResult = $conn->query("SELECT * FROM Justmycupoftea_orders $orderSearchQuery LIMIT $start, $limit");
$orders = $orderResult->fetch_all(MYSQLI_ASSOC);

$totalOrdersCount = $conn->query("SELECT COUNT(*) FROM Justmycupoftea_orders $orderSearchQuery")->fetch_row()[0];
$totalOrderPages = ceil($totalOrdersCount / $limit);

// Handle delete (Customers)
if (isset($_GET['delete'])) {
    $member_id = $_GET['delete'];
    $conn->query("DELETE FROM Justmycupoftea_members WHERE member_id = $member_id");
    header("Location: admin_dashboard.php"); // Refresh page
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<?php include "inc/head.inc.php"; ?>
<title>Just My Cup of Tea | Admin Dashboard</title>
<style>
    .admin-dashboard body {
        font-family: Roboto, sans-serif;
        background-color: white;
        color: #333;
        margin: 0;
        padding: 0;
    }

    .admin-dashboard .title-container {
        width: 100%;
        background-color: white;
        padding: 20px 0;
        box-sizing: border-box;
    }

    .admin-dashboard .dashboard-title {
        text-align: center;
        margin: 0 auto;
        color: #501287;
        font-size: 2.5em;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        padding-bottom: 15px;
        border-bottom: 2px solid #E6A8D7;
        width: 100%;
    }

    .admin-dashboard .container {
        width: 90%;
        margin: 20px auto;
        padding: 2rem;
    }

    .admin-dashboard .order-metrics {
        display: flex;
        justify-content: space-around;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .admin-dashboard .metric-box {
        background-color: #501287;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
        color: white;
        width: 30%;
        margin-right: 0;
        margin-bottom: 20px;
        box-sizing: border-box;
        min-width: 250px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .admin-dashboard .metric-box h3 {
        margin-bottom: 15px;
        font-weight: bold;
        font-size: 1.2em;
    }

    .admin-dashboard .metric-box p {
        font-size: 1.1em;
    }

    .admin-dashboard h1 {
        color: #501287;
        text-align: center;
        font-size: 2.0em;
        font-weight: 600;
        margin-bottom: 25px;
        padding: 10px 0;
        border-bottom: 1px solid #E6A8D7;
    }

    .admin-dashboard table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
    }

    .admin-dashboard th, .admin-dashboard td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #ddd;
        width: 16.66%;
        line-height: 1.5;
    }

    .admin-dashboard th {
        background-color: #E6A8D7;
        color: #501287;
        font-weight: 600;
    }

    .admin-dashboard tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .admin-dashboard a {
        color: white;
        text-decoration: none;
        padding: 8px 12px;
        border-radius: 5px;
        transition: background-color 0.3s ease;
        margin: 5px;
        width: 70px;
        line-height: 30px;
        text-align: center;
        display: inline-block;
    }

    .admin-dashboard a:hover {
        background-color: #7e57c2;
    }

    .admin-dashboard a[href*="edit_customer.php"] {
        background-color: #501287;
    }

    .admin-dashboard a[href*="delete="] {
        background-color: red;
    }

    .search-container {
        text-align: center;
        margin-bottom: 20px;
    }

    .search-container input[type="text"] {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }

    .search-container button {
        padding: 8px 16px;
        background-color: #501287;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .search-container button:hover {
        background-color: #7e57c2;
    }

    .pagination {
        text-align: center;
        margin-top: 20px;
    }

    .pagination a {
        display: inline-block;
        padding: 8px 16px;
        text-decoration: none;
        background-color: #f0f0f0;
        color: #333;
        border-radius: 5px;
        margin: 0 5px;
    }

    .pagination a.active {
        background-color: #501287;
        color: white;
    }

    .pagination a:hover {
        background-color: #ddd;
    }
</style>
</head>
<body>
    <?php include "inc/nav.inc.php"; ?>
    <div class="admin-dashboard">
        <div class="title-container">
            <div class="dashboard-title">Admin Dashboard</div>
        </div>
        <div class="container">
            <div class="order-metrics">
                <div class="metric-box">
                    <h3>Total Orders</h3>
                    <p><?php echo $totalOrders; ?></p>
                </div>
                <div class="metric-box">
                    <h3>Total Sales</h3>
                    <p>$<?php echo number_format($totalSales, 2); ?></p>
                </div>
                <div class="metric-box">
                    <h3>Average Order Value</h3>
                    <p>$<?php echo number_format($averageOrderValue, 2); ?></p>
                </div>
            </div>

            <h1>Users Management</h1>

            <div class="search-container">
                <form id="searchForm" method="get">
                    <input type="text" name="search" placeholder="Search by email" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?php echo $customer['member_id']; ?></td>
                            <td><?php echo $customer['fname']; ?></td>
                            <td><?php echo $customer['lname']; ?></td>
                            <td><?php echo $customer['email']; ?></td>
                            <td><?php echo $customer['role']; ?></td>
                            <td>
                                <a href="admin_edit_customer.php?id=<?php echo $customer['member_id']; ?>">Edit</a>
                                <a href="admin_dashboard.php?delete=<?php echo $customer['member_id']; ?>" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="admin_dashboard.php?page=<?php echo ($page - 1); ?><?php if (!empty($search)) echo '&search=' . htmlspecialchars($search); ?>">Prev</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="admin_dashboard.php?page=<?php echo $i; ?><?php if (!empty($search)) echo '&search=' . htmlspecialchars($search); ?>" <?php if ($i == $page) echo 'class="active"'; ?>><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="admin_dashboard.php?page=<?php echo ($page + 1); ?><?php if (!empty($search)) echo '&search=' . htmlspecialchars($search); ?>">Next</a>
                <?php endif; ?>
            </div>

            <h1>Orders</h1>

            <div class="search-container">
                <form id="orderSearchForm" method="get">
                    <input type="text" name="orderSearch" placeholder="Search by order ID" value="<?php echo htmlspecialchars($orderSearch); ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Member ID</th>
                        <th>Total Amount</th>
                        <th>Payment Intent ID</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Points Redeemed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['order_id']; ?></td>
                            <td><?php echo $order['member_id']; ?></td>
                            <td><?php echo $order['total_amount']; ?></td>
                            <td><?php echo $order['payment_intent_id']; ?></td>
                            <td><?php echo $order['status']; ?></td>
                            <td><?php echo $order['created_at']; ?></td>
                            <td><?php echo $order['points_redeemed']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="admin_dashboard.php?page=<?php echo ($page - 1); ?><?php if (!empty($orderSearch)) echo '&orderSearch=' . htmlspecialchars($orderSearch); ?>">Prev</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalOrderPages; $i++): ?>
                    <a href="admin_dashboard.php?page=<?php echo $i; ?><?php if (!empty($orderSearch)) echo '&orderSearch=' . htmlspecialchars($orderSearch); ?>" <?php if ($i == $page) echo 'class="active"'; ?>><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalOrderPages): ?>
                    <a href="admin_dashboard.php?page=<?php echo ($page + 1); ?><?php if (!empty($orderSearch)) echo '&orderSearch=' . htmlspecialchars($orderSearch); ?>">Next</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include "inc/footer.inc.php"; ?>

    <script>
        // You can add JavaScript here if needed.
    </script>
</body>
</html>
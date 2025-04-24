<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-sm navbar-light">
    <a class="navbar-brand" href="/index.php">
        <img src="images/logos/bubbletea_logo.png" alt="Just My Cup of Tea Logo" class="logo">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
            <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>
                <li class="nav-item">
                    <a class="nav-link" href="admin_dashboard.php">Admin Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="admin_menu.php">Menu</a>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="menu.php">Menu</a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="contact-us.php">Contact</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="about-us.php">About Us</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="quiz.php">Bubble Tea Quiz</a>
            </li>
            <?php if (isset($_SESSION['member_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="purchase_history.php">Orders</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="points.php">Points</a>
                </li>
            <?php endif; ?>
        </ul>

        <div class="ms-auto d-flex align-items-center">
            <?php if (isset($_SESSION["email"])): ?>
                <a href="cart.php" id="cart-link" class="me-3">
                    <img src="images/logos/shopping_cart.png" alt="Cart Icon" class="img-fluid" style="width: 2.5em; height: auto;">
                </a>
                <a href="user_profile.php" id="profile-link" class="me-3">
                    <img src="images/logos/profile.png" alt="Profile Icon" class="img-fluid" style="width: 2.5em; height: auto;">
                </a>
                <a href="logout.php" id="logout-link" class="me-3">
                    <img src="images/logos/logout.png" alt="Logout Icon" class="img-fluid" style="width: 2.5em; height: auto;">
                </a>
            <?php else: ?>
                <a href="#" id="openLoginModal" class="me-3">
                    <img src="images/logos/login.png" alt="Login Icon" class="img-fluid" style="width: 2.5em; height: auto;">
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<?php include 'login_register.php'; ?>

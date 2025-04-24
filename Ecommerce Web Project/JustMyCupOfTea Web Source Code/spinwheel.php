<!DOCTYPE html>
<html lang="en">

<head>
  <title>Spin the Wheel</title>

  <?php include "inc/head.inc.php"; ?>

  <link rel="stylesheet" href="CSS/style.css">
  <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">

  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
</head>

<body>
  <header>
    <?php include "inc/nav.inc.php"; ?>
  </header>

  <main>
    <h1 class="visually-hidden">Spin the Wheel Game</h1>

    <?php include "inc/spinwheel.inc.php"; ?>
  </main>

  <?php include "inc/footer.inc.php"; ?>
</body>

</html>
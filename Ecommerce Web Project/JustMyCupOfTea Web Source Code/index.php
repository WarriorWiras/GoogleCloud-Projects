<?php
session_start();
$show2FAPrompt = false;

$config = parse_ini_file('/var/www/private/db-config.ini');
$conn = new mysqli($config['servername'], $config['username'], $config['password'], $config['dbname']);

if (!$conn->connect_error) {
  $stmt = $conn->prepare("SELECT 2fa_secret FROM Justmycupoftea_members WHERE email = ?");
  $stmt->bind_param("s", $_SESSION["email"]);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    if (!empty($row["2fa_secret"]) && !isset($_SESSION["2fa_verified"])) {
      header("Location: 2fa_verify.php");
      exit();
    }
    if (empty($row["2fa_secret"]) && !isset($_SESSION["dismiss_2fa_prompt"])) {
      $show2FAPrompt = true;
    }
  }
  $stmt->close();
  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <title>Just My Cup Of Tea</title>
  <?php include "inc/head.inc.php"; ?>
  <link rel="stylesheet" href="CSS/index.css" />
  <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
</head>

<body>
  <?php include "inc/nav.inc.php"; ?>

  <main>
    <?php if ($show2FAPrompt): ?>
      <div class="container-md mt-3">
        <div class="alert alert-warning d-flex justify-content-between align-items-center">
          <div>
            <strong>Enhance your security!</strong> Want to enable Two-Factor Authentication?
          </div>
          <div>
            <a href="setup_2fa.php" class="btn btn-success me-2">Enable 2FA</a>
            <form method="post" action="dismiss_2fa_prompt.php" style="display: inline;">
              <button type="submit" class="btn btn-secondary">Dismiss for now</button>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <section class="container-md">
      <h2 class="visually-hidden">Featured Carousel</h2>
      <div id="carouselExampleAutoplaying" class="carousel slide py-3" data-bs-ride="carousel" data-aos="zoom-in" data-aos-duration="1000">
        <div class="carousel-inner rounded-4">
          <div class="carousel-item active" data-bs-interval="3000">
            <img src="images/homepage/banner_8.jpg" alt="" class="d-block banner w-100 rounded-4">
          </div>
          <div class="carousel-item" data-bs-interval="3000">
            <img src="images/homepage/banner_9.jpg" alt="" class="d-block banner w-100 rounded-4">
          </div>
          <div class="carousel-item" data-bs-interval="3000">
            <img src="images/homepage/banner_3.jpg" alt="" class="d-block banner w-100 rounded-4">
          </div>
          <div class="carousel-item" data-bs-interval="3000">
            <img src="images/homepage/banner_4.jpg" alt="" class="d-block banner w-100 rounded-4">
          </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleAutoplaying" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
    </section>

    <section class="container-md message-title text-center p-5 m-auto" data-aos="flip-up" data-aos-duration="1000">
      <h1>POP, SIP, SMILE!</h1>
      <h2 class="w-75 m-auto">At Just My Cup of Tea, we make bubble tea magic happen. Cute cups, chewy pearls, and all the flavors your taste buds dream about. Because life’s too short for boring drinks!</h2>
    </section>

    <section class="container-md items-title text-center" data-aos="flip-up" data-aos-duration="1000">
      <h2>LATEST DEALS</h2>
    </section>

    <section class="container-md closer-look-carousel">
      <h2 class="visually-hidden">Promotional Carousel</h2>
      <div id="carouselExampleAutoplaying2" class="carousel slide carousel-fade py-3" data-bs-ride="carousel" data-aos="zoom-in" data-aos-duration="1000">
        <div class="carousel-inner rounded-4">
          <div class="carousel-item active" data-bs-interval="3000">
            <img src="images/homepage/promotion_1.png" alt="" class="d-block banner w-100 rounded-4">
          </div>
          <div class="carousel-item" data-bs-interval="3000">
            <img src="images/homepage/promotion_2.png" alt="" class="d-block banner w-100 rounded-4">
          </div>
          <div class="carousel-item" data-bs-interval="3000">
            <img src="images/homepage/promotion_3.png" alt="" class="d-block banner w-100 rounded-4">
          </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleAutoplaying2" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleAutoplaying2" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
    </section>

    <?php if (isset($_SESSION["email"])): ?>
      <div class="text-center my-5">
        <a href="spinwheel.php" class="btn btn-lg px-4 py-3 shadow rounded-pill d-inline-flex align-items-center gap-2" style="background-color: #E6A8D7; color: #501287; border: none;">
          <i class="bi bi-gift-fill"></i>
          Feeling lucky? CLICK HERE to win awesome prizes!
        </a>
      </div>
    <?php endif; ?>

    <div class="container-md items-carousel">
      <div class="row pt-4">
        <div class="col-lg-6 col-sm-12">
          <div class="container-md items-title text-center" data-aos="flip-up" data-aos-duration="1000">
            <h2>SIGNATURE MILK TEA</h2>
          </div>
          <div id="carouselExampleAutoplaying3" class="carousel slide my-5" data-bs-ride="carousel" data-aos="zoom-in"
            data-aos-duration="1000">
            <div class="carousel-inner rounded-4">
              <div class="carousel-item active" data-bs-interval="3000">
                <img src="images/drinks/hazelnut_milk_tea.jpeg" alt="" class="d-block small-banner w-100 rounded-4">
                <div class="carousel-caption d-md-block ">
                  <p class="h3 m-auto"></p>
                </div>
              </div>

              <div class="carousel-item" data-bs-interval="3000">
                <img src="images/drinks/lychee_milk_tea.jpeg" alt="" class="d-block small-banner w-100 rounded-4">
                <div class="carousel-caption d-md-block ">
                  <p class="h3 m-auto"></p>
                </div>
              </div>

              <div class="carousel-item" data-bs-interval="3000">
                <img src="images/drinks/oolong_milk_tea.jpeg" alt="" class="d-block small-banner w-100 rounded-4">
                <div class="carousel-caption d-md-block ">
                  <p class="h3 m-auto"></p>
                </div>
              </div>

              <div class="carousel-item" data-bs-interval="3000">
                <img src="images/drinks/milk_tea.jpeg" alt="" class="d-block small-banner w-100 rounded-4">
                <div class="carousel-caption d-md-block ">
                  <p class="h3 m-auto"></p>
                </div>
              </div>

              <div class="carousel-item" data-bs-interval="3000">
                <img src="images/drinks/strawberry_milk_tea.jpeg" alt="" class="d-block small-banner w-100 rounded-4">
                <div class="carousel-caption d-md-block ">
                  <p class="h3 m-auto"></p>
                </div>
              </div>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleAutoplaying3"
              data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Previous</span>
            </button>

            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleAutoplaying3"
              data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Next</span>
            </button>
          </div>
        </div>

        <div class="col-lg-6 col-sm-12">
          <div class="container-md items-title text-center" data-aos="flip-up" data-aos-duration="1000">
            <h2>SIGNATURE MACCHIATO</h2>
          </div>
          <div id="carouselExampleAutoplaying4" class="carousel slide my-5" data-bs-ride="carousel" data-aos="zoom-in"
            data-aos-duration="1000">
            <div class="carousel-inner rounded-4">
              <div class="carousel-item active" data-bs-interval="3000">
                <img src="images/drinks/green_tea_macchiato.jpeg" alt="" class="d-block small-banner w-100 rounded-4">
                <div class="carousel-caption d-md-block ">
                  <p class="h3 m-auto"></p>
                </div>
              </div>

              <div class="carousel-item" data-bs-interval="3000">
                <img src="images/drinks/lychee_black_tea_macchiato.jpeg" alt=""
                  class="d-block small-banner w-100 rounded-4">
                <div class="carousel-caption d-md-block ">
                  <p class="h3 m-auto"></p>
                </div>
              </div>

              <div class="carousel-item" data-bs-interval="3000">
                <img src="images/drinks/strawberry_macchiato.jpeg" alt="" class="d-block small-banner w-100 rounded-4">
                <div class="carousel-caption d-md-block ">
                  <p class="h3 m-auto"></p>
                </div>
              </div>

            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleAutoplaying4"
              data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Previous</span>
            </button>

            <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleAutoplaying4"
              data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
              <span class="visually-hidden">Next</span>
            </button>
          </div>
        </div>
      </div>
    </div>

      <section class="container-md unbox-title text-center p-3" data-aos="flip-up" data-aos-duration="1000">
        <h2>HOW TO GET YOUR BUBBLE TEA?</h2>
        <h3>IT'S AS EASY AS 1, 2, 3!</h3>
      </section>

      <section class="container-md cards">
        <div class="card-group mx-3">
          <div class="card mx-3 rounded-4" data-aos="zoom-in" data-aos-duration="1000">
            <img src="images/homepage/step1.jpg" class="card-img-top rounded-top-3" alt="...">
            <div class="card-body">
              <h3 class="card-title">PICK YOUR FLAVOUR</h3>
            </div>
          </div>

          <div class="card mx-3 rounded-4" data-aos="zoom-in" data-aos-duration="1000">
            <img src="images/homepage/step2.jpg" class="card-img-top rounded-top-3" alt="...">
            <div class="card-body">
              <h3 class="card-title">ADD YOUR TOPPINGS</h3>
            </div>
          </div>

          <div class="card mx-3 rounded-4" data-aos="zoom-in" data-aos-duration="1000">
            <img src="images/homepage/step4.jpg" class="card-img-top rounded-top-3" alt="...">
            <div class="card-body">
              <h3 class="card-title">AND SIP AWAY!</h3>
            </div>
          </div>
        </div>
      </section>
  </main>

  <?php include "inc/footer.inc.php"; ?>
</body>

</html>
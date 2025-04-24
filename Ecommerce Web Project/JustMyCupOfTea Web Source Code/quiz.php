<!DOCTYPE html>
<html lang="en">

<head>
  <?php
  include "inc/head.inc.php";
  ?>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>What Bubble Tea Are You?</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #fef3f5;
      color: #333;
      padding: 20px;
      text-align: center;
    }

    h1 {
      color: #d946ef;
    }

    .quiz-container {
      background-color: #fff;
      padding: 20px;
      border-radius: 15px;
      max-width: 600px;
      margin: 0 auto;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .question {
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin: 8px 0;
    }

    button {
  background-color: #d6336c;  /* Darker background color */
  color: #ffffff;  /* White text */
  border: none;
  padding: 10px 20px;
  margin-top: 10px;
  font-size: 16px;
  font-weight: normal;
  border-radius: 8px;
  cursor: pointer;
}

button:hover {
  background-color: #c0273e;  /* Darker hover effect */
}


    #result {
      margin-top: 30px;
      font-size: 1.2em;
      font-weight: bold;
      color: #9333ea;
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <?php
  include "inc/nav.inc.php";
  ?>
  <header>
    <h1>✨ What Bubble Tea Are You? 🧋</h1>
  </header>

  <main>
    <div class="quiz-container">
      <form id="quizForm">
        <div class="question">
          <p>1. What's your vibe?</p>
          <label><input type="radio" name="q1" value="fun" required> Fun & bubbly</label>
          <label><input type="radio" name="q1" value="chill"> Chill & laid-back</label>
          <label><input type="radio" name="q1" value="classic"> Classic & dependable</label>
        </div>

        <div class="question">
          <p>2. Pick a color:</p>
          <label><input type="radio" name="q2" value="pink" required> Pink</label>
          <label><input type="radio" name="q2" value="green"> Green</label>
          <label><input type="radio" name="q2" value="brown"> Brown</label>
        </div>

        <div class="question">
          <p>3. Your go-to weekend activity?</p>
          <label><input type="radio" name="q3" value="dance" required> Dancing or partying</label>
          <label><input type="radio" name="q3" value="nature"> Nature walk or picnic</label>
          <label><input type="radio" name="q3" value="movie"> Binge-watch movies</label>
        </div>

        <button type="submit">Find Out!</button>
      </form>

      <div id="result"></div>
    </div>
  </main>

  <script>
    document.getElementById('quizForm').addEventListener('submit', function (e) {
      e.preventDefault();

      const q1 = document.querySelector('input[name="q1"]:checked').value;
      const q2 = document.querySelector('input[name="q2"]:checked').value;
      const q3 = document.querySelector('input[name="q3"]:checked').value;

      let result = "";

      if (q1 === "fun" && q2 === "pink" && q3 === "dance") {
        result = "🍓 You're a Strawberry Milk Tea! Sweet, vibrant, and always the life of the party!";
      } else if (q1 === "chill" && q2 === "green" && q3 === "nature") {
        result = "🍵 You're a Matcha Bubble Tea! Calm, grounded, and full of depth.";
      } else if (q1 === "classic" && q2 === "brown" && q3 === "movie") {
        result = "🧋 You're a Classic Brown Sugar Boba! Timeless, dependable, and everyone loves you.";
      } else {
        result = "🍹 You're a Fruit Tea Fusion! A perfect blend of unpredictability and flavor!";
      }

      document.getElementById('result').innerText = result;
    });
  </script>
  <?php
  include "inc/footer.inc.php";
  ?>
</body>

</html>
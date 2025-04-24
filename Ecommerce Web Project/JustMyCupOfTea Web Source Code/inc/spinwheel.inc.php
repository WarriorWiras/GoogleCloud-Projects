<!-- AOS Animation -->
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<!-- Spin Wheel JS -->
<script>
  document.addEventListener("DOMContentLoaded", function () {
    AOS.init();

    const rewards = [
      "Free Topping", "10% Off", "Buy 1 Get 1",
      "Free Upgrade", "Try Again", "Mystery Gift"
    ];

    let startAngle = 0;
    const arc = Math.PI / (rewards.length / 2);
    const canvas = document.getElementById("wheelCanvas");
    const ctx = canvas.getContext("2d");

    const dpr = window.devicePixelRatio || 1;
    const size = 300;
    canvas.width = size * dpr;
    canvas.height = size * dpr;
    canvas.style.width = size + "px";
    canvas.style.height = size + "px";
    ctx.scale(dpr, dpr);

    const spinBtn = document.getElementById("spinBtn");
    const resultDisplay = document.getElementById("wheelResult");
    const codeDisplay = document.getElementById("promoCode");

    let spinning = false;

    const cooldownKey = "lastSpinTime";
    const cooldownPeriod = 3 * 24 * 60 * 60 * 1000; // 3 days in ms

    function drawWheel() {
      const outsideRadius = 120;
      const textRadius = 75;
      const insideRadius = 0;
      ctx.clearRect(0, 0, 300, 300);

      for (let i = 0; i < rewards.length; i++) {
        const angle = startAngle + i * arc;
        ctx.fillStyle = i % 2 === 0 ? "#E6A8D7" : "#501287";
        ctx.beginPath();
        ctx.arc(150, 150, outsideRadius, angle, angle + arc, false);
        ctx.arc(150, 150, insideRadius, angle + arc, angle, true);
        ctx.fill();

        ctx.save();
        ctx.fillStyle = "white";
        ctx.font = "bold 13px Arial";
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.translate(
          150 + Math.cos(angle + arc / 2) * textRadius,
          150 + Math.sin(angle + arc / 2) * textRadius
        );
        ctx.rotate(angle + arc / 2);
        wrapText(ctx, rewards[i], 0, 0, 70, 14);
        ctx.restore();
      }
    }

    function wrapText(ctx, text, x, y, maxWidth, lineHeight) {
      const words = text.split(' ');
      let line = '', lines = [];

      for (let n = 0; n < words.length; n++) {
        const testLine = line + words[n] + ' ';
        const testWidth = ctx.measureText(testLine).width;
        if (testWidth > maxWidth && n > 0) {
          lines.push(line);
          line = words[n] + ' ';
        } else {
          line = testLine;
        }
      }
      lines.push(line);
      const offset = -(lines.length - 1) * lineHeight / 2;
      for (let i = 0; i < lines.length; i++) {
        ctx.fillText(lines[i], x, y + offset + i * lineHeight);
      }
    }

    function spinWheel() {
      if (spinning) return;
      spinning = true;
      spinBtn.disabled = true;

      const spinAngle = Math.random() * 360 + 1800;
      const duration = 3000;
      const start = performance.now();

      function animate(now) {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const angle = spinAngle * easeOut(progress);
        startAngle = (angle * Math.PI / 180) % (2 * Math.PI);
        drawWheel();

        if (progress < 1) {
          requestAnimationFrame(animate);
        } else {
          spinning = false;

          const degrees = spinAngle % 360;
          const index = Math.floor((360 - degrees + 90) % 360 / (360 / rewards.length));
          const reward = rewards[index];

          if (reward === "Try Again") {
            resultDisplay.innerText = "Try Again!";
            codeDisplay.innerText = "";
          } else {
            resultDisplay.innerText = `You won: ${reward}!`;
            const promoCode = "BOBA-" + Math.random().toString(36).substring(2, 10).toUpperCase();
            codeDisplay.innerText = `Promo Code: ${promoCode}`;
          }

          confetti({ particleCount: 100, spread: 70, origin: { y: 0.6 } });
          document.getElementById("winSound").play();

          // Save spin time
          localStorage.setItem(cooldownKey, Date.now().toString());
          disableButtonForCooldown();
        }
      }

      requestAnimationFrame(animate);
    }

    function easeOut(t) {
      return 1 - Math.pow(1 - t, 3);
    }

    function disableButtonForCooldown() {
      const now = Date.now();
      const nextSpinTime = new Date(now + cooldownPeriod);
      spinBtn.disabled = true;
      spinBtn.textContent = `Come back on ${nextSpinTime.toLocaleDateString()} to spin again!`;
    }

    // Check cooldown on load
    const lastSpinTime = parseInt(localStorage.getItem(cooldownKey));
    if (!isNaN(lastSpinTime)) {
      const now = Date.now();
      if (now - lastSpinTime < cooldownPeriod) {
        disableButtonForCooldown();
      }
    }

    window.spinWheel = spinWheel;
    drawWheel();
  });
</script>

<!-- HTML Wheel UI -->
<div class="container my-4">
  <div class="card mx-auto text-center p-4 shadow rounded-4" style="max-width: 420px;" data-aos="zoom-in">
    <div class="card-body">
      <h2 class="card-title fw-bold text-uppercase mb-4" style="color: #501287; letter-spacing: 1px;">
        Spin the Wheel
      </h2>

      <div class="d-flex justify-content-center position-relative mb-3">
        <div class="position-absolute bottom-0 start-50 translate-middle-x" style="z-index: 2;">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="red">
            <path d="M12 2L6 12h12L12 2z" />
          </svg>
        </div>
        <canvas id="wheelCanvas" width="300" height="300"
          class="border border-2 border-primary-subtle rounded-circle bg-light"></canvas>
      </div>

      <button onclick="spinWheel()" id="spinBtn"
        class="btn btn-lg btn-primary mt-2 px-4 py-2 rounded-pill shadow-sm">🧋 Spin Now</button>

      <p id="wheelResult" class="mt-4 fw-semibold text-success fs-5"></p>
      <p id="promoCode" class="mt-2 fw-bold text-primary fs-6"></p>
    </div>
  </div>
</div>

<audio id="winSound" src="https://assets.mixkit.co/sfx/preview/mixkit-achievement-bell-600.wav" preload="auto"></audio>
<script src="https://www.google.com/recaptcha/api.js?render=explicit" async defer></script>

<!-- Auth Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="authModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-header border-bottom-0">
        <h5 class="modal-title" id="authModalLabel">Welcome to Just My Cup of Tea!</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <!-- Login Form -->
        <form id="loginForm">
          <div id="loginSection">
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control"  required value="<?php echo isset($_COOKIE['email']) ? htmlspecialchars($_COOKIE['email']) : ''; ?>">
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="pwd" class="form-control" required>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="remember" id="rememberLogin">
              <label class="form-check-label" for="rememberLogin">Remember Me</label>
            </div>
            
            
            <div id="login-captcha" class="mb-3"></div>
            
            <div id="loginError" class="text-danger mb-2"></div>
            <button type="submit" class="btn btn-success w-100">Login</button>
            <p class="text-center mt-3 mb-0">
              Don’t have an account? <a href="#" id="showRegister">Register Here!</a>
            </p>
          </div>
        </form>

        <!-- Register Form with visible reCAPTCHA -->
        <form id="registerForm" style="display: none;">
          <div id="registerSection">
            <div class="row">
              <div class="col mb-3">
                <label class="form-label">First Name</label>
                <input type="text" name="fname" class="form-control" required>
              </div>
              <div class="col mb-3">
                <label class="form-label">Last Name (Optional)</label>
                <input type="text" name="lname" class="form-control" >
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password (Must be 8 characters long, including uppercase, lowercase, number, and special character)</label>
                <input type="password" name="pwd" class="form-control" required
                pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$"
                title="Must be at least 8 characters long and include uppercase, lowercase, number, and special character.">
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="pwd_confirm" class="form-control" required>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="agree" id="agree" required>
              <label class="form-check-label" for="agree">I agree to the terms and conditions</label>
            </div>

       
            <div id="register-captcha" class="mb-3"></div>
            
            <div id="registerError" class="text-danger mb-2"></div>
            <button type="submit" class="btn btn-success w-100">Register</button>
            <p class="text-center mt-3 mb-0">
              Already have an account? <a href="#" id="showLogin">Login Here!</a>
            </p>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>


<script>
  // Password Validation
  const passwordInput = document.querySelector('#registerForm input[name="pwd"]');
  const confirmInput = document.querySelector('#registerForm input[name="pwd_confirm"]');

  function validatePasswordMatch() {
    confirmInput.setCustomValidity(
      passwordInput.value !== confirmInput.value ? "Passwords do not match" : ""
    );
  }

  passwordInput.addEventListener('input', validatePasswordMatch);
  confirmInput.addEventListener('input', validatePasswordMatch);
</script>

<?php
$apiKeys = parse_ini_file('/var/www/private/api-keys.ini');
?>

<script>
let loginCaptchaWidgetId;
let registerCaptchaWidgetId;

const siteKey = "<?= htmlspecialchars($apiKeys['google_site_key'], ENT_QUOTES, 'UTF-8') ?>";

window.addEventListener("load", function () {
  grecaptcha.ready(function () {
    // Render the login captcha if not already rendered
    if (!loginCaptchaWidgetId) {
      loginCaptchaWidgetId = grecaptcha.render('login-captcha', {
        sitekey: siteKey
      });
    }

    // Render the register captcha if not already rendered
    if (!registerCaptchaWidgetId) {
      registerCaptchaWidgetId = grecaptcha.render('register-captcha', {
        sitekey: siteKey
      });
    }
  });
});
</script>



<script>
document.addEventListener("DOMContentLoaded", function () {
  const loginModalElement = document.getElementById('loginModal');
  const loginModal = new bootstrap.Modal(loginModalElement);
  
  const openLoginBtn = document.getElementById('openLoginModal');
  const loginForm    = document.getElementById('loginForm');
  const registerForm = document.getElementById('registerForm');
  
  const showRegister = document.getElementById('showRegister');
  const showLogin    = document.getElementById('showLogin');

  // Show modal with login form by default
  openLoginBtn?.addEventListener('click', function (e) {
    e.preventDefault();
    loginForm.style.display    = 'block';
    registerForm.style.display = 'none';
    
    
    if (typeof grecaptcha !== 'undefined') {
      grecaptcha.reset(loginCaptchaWidgetId);
      grecaptcha.reset(registerCaptchaWidgetId);
    }
    loginModal.show();
  });

  // Switch from Login -> Register
  showRegister?.addEventListener('click', function (e) {
    e.preventDefault();
    loginForm.style.display    = 'none';
    registerForm.style.display = 'block';

    
    if (typeof grecaptcha !== 'undefined') {
      grecaptcha.reset(registerCaptchaWidgetId);
    }
  });

  // Switch from Register -> Login
  showLogin?.addEventListener('click', function (e) {
    e.preventDefault();
    registerForm.style.display = 'none';
    loginForm.style.display    = 'block';

   
    if (typeof grecaptcha !== 'undefined') {
      grecaptcha.reset(loginCaptchaWidgetId);
    }
  });

  // ========== LOGIN FORM SUBMIT (AJAX) ========== 
  loginForm?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const captchaResponse = grecaptcha.getResponse(loginCaptchaWidgetId);
    if (!captchaResponse) {
      document.getElementById('loginError').textContent = "Please complete the reCAPTCHA.";
      return;
    }

    const formData = new FormData(loginForm);
    formData.append('g-recaptcha-response', captchaResponse);

    const response = await fetch('process_login.php', {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const result = await response.text();

    if (result.trim() === 'success') {
      // If login is successful, refresh page or redirect
      location.reload();
    } 
    //check if user enabled 2fa before continuing
    else if (result.trim() === '2FA_REQUIRED') {
    window.location.href = '2fa_verify.php';}
     else {
      document.getElementById('loginError').textContent = result.trim();
      // Reset captcha so user can try again
      grecaptcha.reset(loginCaptchaWidgetId);
    }
  });

  // ========== REGISTER FORM SUBMIT (AJAX) ========== 
  registerForm?.addEventListener('submit', async function (e) {
    e.preventDefault();

    const captchaResponse = grecaptcha.getResponse(registerCaptchaWidgetId);
    if (!captchaResponse) {
      document.getElementById('registerError').textContent = "reCAPTCHA not completed. Please try again.";
      return;
    }

    const formData = new FormData(registerForm);
    // Include the token
    formData.append('g-recaptcha-response', captchaResponse);

    const response = await fetch('process_register.php', {
      method: 'POST',
      body: formData,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });

    const result = await response.text();

    if (result.trim() === 'success') {
      // Registration successful
      alert('Registration Successful!');
      location.reload();
    } else {
      document.getElementById('registerError').textContent = result.trim();
      // Reset the captcha
      grecaptcha.reset(registerCaptchaWidgetId);
    }
  });
});
</script>

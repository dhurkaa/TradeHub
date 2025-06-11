<?php
session_start();
include 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$show_verification = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['signup'])) {
  $name     = $_POST["name"];
  $email    = $_POST["email"];
  $phone    = $_POST["phone"];
  $location = $_POST["city"] . ", " . $_POST["country"];
  $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
  $code     = rand(100000, 999999);
  $token    = bin2hex(random_bytes(32));

  $check = $conn->prepare("SELECT id FROM tradehub_users WHERE email = ?");
  $check->bind_param("s", $email);
  $check->execute();
  $check->store_result();

  if ($check->num_rows > 0) {
    $message = "❌ This email is already registered.";
  } else {
    $stmt = $conn->prepare("INSERT INTO tradehub_users (name, email, phone, location, password, verification_code, verify_token) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $name, $email, $phone, $location, $password, $code, $token);

    if ($stmt->execute()) {
      $_SESSION['verify_user_id'] = $stmt->insert_id;

      $verify_link = "https://cobifiles.site/tradehub/verify.php?token=$token";
      $subject = "Verify your TradeHub account";
      $body = "Hi $name,\n\nYou can verify your account in two ways:\n\n"
            . "1. Click this link: $verify_link\n"
            . "2. Or enter this 6-digit code on the site: $code\n\n"
            . "Thanks,\nTradeHub Team";
      $headers = "From: no-reply@cobifiles.site";

      mail($email, $subject, $body, $headers);

      $message = "✅ Verification link and code sent! Check your email.";
      $show_verification = true;
    } else {
      $message = "❌ Something went wrong. Try again.";
    }
  }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['verify_code'])) {
  $input_code = $_POST['code'];
  $user_id = $_SESSION['verify_user_id'] ?? null;

  if ($user_id) {
    $verify_stmt = $conn->prepare("SELECT verification_code FROM tradehub_users WHERE id = ?");
    $verify_stmt->bind_param("i", $user_id);
    $verify_stmt->execute();
    $verify_stmt->bind_result($correct_code);
    $verify_stmt->fetch();
    $verify_stmt->close();

    if ($input_code == $correct_code) {
      $update_stmt = $conn->prepare("UPDATE tradehub_users SET is_verified = 1, verify_token = NULL WHERE id = ?");
      $update_stmt->bind_param("i", $user_id);
      $update_stmt->execute();
      $update_stmt->close();

      unset($_SESSION['verify_user_id']);
      header("Location: home.php");
      exit;
    } else {
      $message = "❌ Incorrect verification code.";
      $show_verification = true;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>TradeHub Sign Up</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f2f6ff, #e5ecff);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }
    .signup-box {
      background-color: #ffffff;
      width: 480px;
      max-width: 100%;
      padding: 40px 35px;
      border-radius: 20px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
      text-align: center;
    }
    .logo {
      width: 160px;
      margin-bottom: -20px;
    }
    h2 {
      font-size: 26px;
      margin-bottom: 25px;
      color: #222;
    }
    form {
      display: flex;
      flex-direction: column;
      gap: 16px;
      margin-top: 10px;
    }
    label {
      font-size: 14px;
      color: #333;
      text-align: left;
    }
    input, select {
      padding: 14px;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 15px;
      transition: border 0.3s ease;
    }
    input:focus, select:focus {
      border-color: #0ca6a6;
      outline: none;
      box-shadow: 0 0 0 3px rgba(12, 166, 166, 0.1);
    }
    button {
      padding: 14px;
      margin-top: 8px;
      background: linear-gradient(135deg, #0ca6a6, #10c0c0);
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.2s ease, background 0.3s ease;
    }
    button:hover {
      transform: translateY(-1px);
      background: linear-gradient(135deg, #089e9e, #0db7b7);
    }
    .login-link {
      margin-top: 24px;
      font-size: 14px;
      color: #444;
    }
    .login-link a {
      color: #0ca6a6;
      text-decoration: none;
      font-weight: 500;
    }
    .login-link a:hover {
      text-decoration: underline;
    }
    .message {
      margin-top: 18px;
      font-size: 14px;
      color: #0a6;
      font-weight: 500;
    }
    .overlay {
      position: fixed;
      top: 0; left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0,0,0,0.5);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 999;
    }
    .popup {
      background: #fff;
      padding: 35px 30px;
      border-radius: 14px;
      box-shadow: 0 0 20px rgba(0,0,0,0.2);
      width: 320px;
      text-align: center;
    }
    .popup h3 {
      font-size: 20px;
      margin-bottom: 20px;
      color: #222;
    }
    .popup input {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 15px;
    }
    .popup button {
      padding: 12px;
      background: linear-gradient(135deg, #0ca6a6, #10c0c0);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    .popup button:hover {
      background: linear-gradient(135deg, #089e9e, #0db7b7);
    }
    @media (max-width: 500px) {
      .signup-box { padding: 30px 20px; }
      .popup { width: 90%; padding: 25px 20px; }
    }
  </style>
</head>
<body>
  <div class="signup-box">
    <img src="tradehub.png" alt="TradeHub Logo" class="logo" />
    <h2>Sign Up</h2>
    <form method="POST">
      <label for="name">Name</label>
      <input type="text" name="name" required />

      <label for="email">Email</label>
      <input type="email" name="email" required />

      <label for="phone">Phone</label>
      <input type="tel" name="phone" required />

      <label for="country">Country</label>
      <select name="country" id="country" required>
        <option value="">Select Country</option>
      </select>

      <label for="city">City</label>
      <select name="city" id="city" required>
        <option value="">Select City</option>
      </select>

      <label for="password">Password</label>
      <input type="password" name="password" required />

      <button type="submit" name="signup">Sign up</button>
    </form>

    <div class="login-link">
      Already have an account? <a href="login.php">Log in</a>
    </div>

    <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>
  </div>
  <?php if ($show_verification): ?>
    <div class="overlay">
      <div class="popup">
        <h3>Enter Verification Code</h3>
        <form method="POST">
          <input type="text" name="code" placeholder="6-digit code" required />
          <button type="submit" name="verify_code">Verify</button>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <script>
    const countrySelect = document.getElementById("country");
    const citySelect = document.getElementById("city");

    fetch("european_countries_with_cities.json")
      .then(response => {
        if (!response.ok) throw new Error("Failed to load JSON");
        return response.json();
      })
      .then(data => {
        // Populate countries
        for (const country in data) {
          const option = document.createElement("option");
          option.value = country;
          option.textContent = country;
          countrySelect.appendChild(option);
        }

        // Update cities when country is selected
        countrySelect.addEventListener("change", function () {
          const cities = data[this.value] || [];
          citySelect.innerHTML = '<option value="">Select City</option>';
          cities.forEach(city => {
            const option = document.createElement("option");
            option.value = city;
            option.textContent = city;
            citySelect.appendChild(option);
          });
        });
      })
      .catch(error => {
        console.error("❌ JSON loading error:", error);
      });
  </script>
</body>
</html>

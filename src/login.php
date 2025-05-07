<?php
session_start();
include 'config.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = $_POST["email"];
  $password = $_POST["password"];

  $stmt = $conn->prepare("SELECT id, password, is_verified FROM tradehub_users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows == 1) {
    $stmt->bind_result($id, $hashed_password, $is_verified);
    $stmt->fetch();

    if (!password_verify($password, $hashed_password)) {
      $message = "❌ Incorrect password.";
    } elseif ($is_verified != 1) {
      $message = "⚠️ Please verify your account first.";
    } else {
      $_SESSION['user_id'] = $id;
      header("Location: home.php");
      exit;
    }
  } else {
    $message = "❌ No user found with that email.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>TradeHub Login</title>
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
    }

    .login-box {
      background-color: #ffffff;
      width: 360px;
      padding: 35px 30px;
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
      text-align: center;
    }

    .logo {
      width: 150px;
      margin-bottom: -22px;
    }

    h2 {
      font-size: 24px;
      margin-bottom: 20px;
      color: #222;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    label {
      font-size: 14px;
      color: #333;
      text-align: left;
    }

    input {
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 15px;
      transition: border 0.3s ease;
    }

    input:focus {
      border-color: #2160ff;
      outline: none;
      box-shadow: 0 0 0 3px rgba(33, 96, 255, 0.1);
    }

    button {
      padding: 12px;
      margin-top: 5px;
      background: linear-gradient(135deg, #2160ff, #4988ff);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: transform 0.2s ease, background 0.3s ease;
    }

    button:hover {
      transform: translateY(-1px);
      background: linear-gradient(135deg, #1a4ed8, #3e76ef);
    }

    .signup-link {
      margin-top: 22px;
      font-size: 14px;
      color: #444;
    }

    .signup-link a {
      color: #2160ff;
      text-decoration: none;
      font-weight: 500;
    }

    .signup-link a:hover {
      text-decoration: underline;
    }

    .message {
      margin-top: 15px;
      font-size: 14px;
      color: #c00;
      font-weight: 500;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <img src="tradehub.png" alt="TradeHub Logo" class="logo" />

    <h2>Log in</h2>

    <form method="POST">
      <label for="email">Email</label>
      <input type="email" name="email" required />

      <label for="password">Password</label>
      <input type="password" name="password" required />

      <button type="submit">Log in</button>
    </form>

    <div class="signup-link">
      Don’t have an account? <a href="signup.php">Sign up</a>
    </div>

    <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>
  </div>
</body>
</html>

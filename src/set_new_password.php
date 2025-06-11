<?php
session_start();
require 'config.php';

if (!isset($_SESSION['reset_verified']) || !isset($_SESSION['reset_email'])) {
  die("Unauthorized.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new = $_POST['new_password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  if ($new !== $confirm) {
    $message = "❌ Passwords do not match.";
  } elseif (strlen($new) < 8) {
    $message = "❌ Password must be at least 8 characters.";
  } else {
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE tradehub_users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hash, $_SESSION['reset_email']);
    $stmt->execute();

    unset($_SESSION['reset_email'], $_SESSION['reset_code'], $_SESSION['reset_verified']);
    $message = "✅ Password updated! <a href='login.php'>Login now</a>";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Set New Password | TradeHub</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #f2f6ff, #e5ecff);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .box {
      background: white;
      width: 360px;
      padding: 35px 30px;
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
      text-align: center;
    }

    h2 {
      margin-bottom: 20px;
      color: #222;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    input {
      padding: 12px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 15px;
    }

    button {
      padding: 12px;
      background: linear-gradient(135deg, #2160ff, #4988ff);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
    }

    .message {
      margin-top: 15px;
      font-size: 14px;
      color: #c00;
      font-weight: 500;
    }

    .message a {
      color: #2160ff;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="box">
    <h2>Set New Password</h2>
    <form method="POST">
      <input type="password" name="new_password" placeholder="New Password" required />
      <input type="password" name="confirm_password" placeholder="Confirm Password" required />
      <button type="submit">Update Password</button>
    </form>
    <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>
  </div>
</body>
</html>

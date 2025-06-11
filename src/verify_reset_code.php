<?php
session_start();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $input = $_POST['code'] ?? '';

  if ($input == ($_SESSION['reset_code'] ?? '')) {
    $_SESSION['reset_verified'] = true;
    header("Location: set_new_password.php");
    exit;
  } else {
    $message = "❌ Code incorrect.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Verify Code | TradeHub</title>
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
      color: red;
      font-size: 14px;
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <div class="box">
    <h2>Verify Code</h2>
    <form method="POST">
      <input type="text" name="code" maxlength="6" placeholder="Enter 6-digit code" required />
      <button type="submit">Verify</button>
    </form>
    <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>
  </div>
</body>
</html>

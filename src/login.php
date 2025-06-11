<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'config.php';

// Log login attempts
function log_login_attempt($conn, $user_id, $email, $status) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $stmt = $conn->prepare("INSERT INTO login_logs (user_id, email_attempted, ip_address, user_agent, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $email, $ip, $agent, $status);
    $stmt->execute();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, password, is_verified, is_2fa_enabled FROM tradehub_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $hashed_password, $is_verified, $is_2fa_enabled);
        $stmt->fetch();

        if (!password_verify($password, $hashed_password)) {
            $message = "❌ Incorrect password.";
            $_SESSION['reset_email'] = $email;
            log_login_attempt($conn, $id, $email, 'failed');
        } elseif ($is_verified != 1) {
            $message = "⚠️ Please verify your account first.";
            log_login_attempt($conn, $id, $email, 'failed');
        } else {
            log_login_attempt($conn, $id, $email, 'success');
            $update = $conn->prepare("UPDATE tradehub_users SET last_login_at = NOW() WHERE id = ?");
            $update->bind_param("i", $id);
            $update->execute();

            if ($is_2fa_enabled) {
                $code = rand(100000, 999999);
                $expires = date('Y-m-d H:i:s', time() + 300);

                $stmt2 = $conn->prepare("UPDATE tradehub_users SET twofa_code = ?, twofa_expires = ? WHERE id = ?");
                $stmt2->bind_param("ssi", $code, $expires, $id);
                $stmt2->execute();

                mail($email, "Your TradeHub 2FA Code", "Your code: $code\nValid 5 mins", "From: no-reply@tradehub.com");

                $_SESSION['2fa_user_id'] = $id;
                $_SESSION['2fa_pending'] = true;
                header("Location: 2fa_verify.php");
                exit;
            } else {
                $_SESSION['user_id'] = $id;
                header("Location: home.php");
                exit;
            }
        }
    } else {
        $message = "❌ No user found with that email.";
        log_login_attempt($conn, null, $email, 'failed');
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
  <script>
   

    function openForgotPopup() {
      document.getElementById('forgotModal').style.display = 'flex';
    }

    function closeForgotModal() {
      document.getElementById('forgotModal').style.display = 'none';
    }
  </script>
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

    .forgot-link {
      color: #2160ff;
      text-decoration: none;
      font-size: 14px;
      display: inline-block;
      margin-top: 5px;
    }

    .forgot-link:hover {
      text-decoration: underline;
    }

    #forgotModal {
      display: none;
      position: fixed;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0, 0, 0, 0.6);
      justify-content: center;
      align-items: center;
    }

    .modal-box {
      background: white;
      padding: 30px;
      width: 320px;
      border-radius: 12px;
      text-align: center;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
    }

    .modal-box h3 {
      margin-bottom: 15px;
      color: #222;
    }

    .modal-box input {
      margin-top: 10px;
      width: 100%;
    }

    .modal-box .close-btn {
      margin-top: 15px;
      background: #ddd;
      color: #000;
    }

    .modal-box .close-btn:hover {
      background: #ccc;
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

    <?php if (!empty($message)): ?>
      <p class="message"><?= $message ?></p>
      <?php if ($message === "❌ Incorrect password." && isset($_SESSION['reset_email'])): ?>
        <a href="#" class="forgot-link" onclick="openForgotPopup()">Forgot Password?</a>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- Forgot Password Modal -->
  <div id="forgotModal">
    <div class="modal-box">
      <h3>Reset Your Password</h3>
      <form action="send_reset_code.php" method="POST">
        <input type="email" name="email" placeholder="Enter your email" value="<?= htmlspecialchars($_SESSION['reset_email'] ?? '') ?>" required />
        <button type="submit">Send Code</button>
        <button type="button" class="close-btn" onclick="closeForgotModal()">Cancel</button>
      </form>
    </div>
  </div>
</body>
</html>

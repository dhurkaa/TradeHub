<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['2fa_user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['2fa_user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredCode = trim($_POST['code']);

    $stmt = $conn->prepare("SELECT twofa_code, twofa_expires FROM tradehub_users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($storedCode, $expires);
    $stmt->fetch();
    $stmt->close();

    if ($storedCode && $enteredCode === $storedCode && strtotime($expires) > time()) {
        $_SESSION['user_id'] = $user_id;
        unset($_SESSION['2fa_user_id']);
        unset($_SESSION['2fa_pending']);
        header("Location: home.php");
        exit();
    } else {
        $message = "Invalid or expired 2FA code.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>2FA Verification | TradeHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
  <div class="bg-white p-8 rounded shadow-lg w-full max-w-md">
    <h2 class="text-2xl font-bold mb-4 text-center">🔐 Two-Factor Authentication</h2>
    <p class="text-sm text-gray-600 mb-4 text-center">We've sent a 6-digit code to your email. Please enter it below.</p>

    <?php if ($message): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 mb-4 rounded"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="text" name="code" maxlength="6" placeholder="Enter 2FA code" required
             class="w-full px-4 py-2 border border-gray-300 rounded mb-4 focus:outline-none focus:ring-2 focus:ring-black text-center text-lg tracking-widest">

      <button type="submit"
              class="w-full bg-black text-white py-2 rounded hover:bg-gray-800 transition font-semibold">
        ✅ Verify & Continue
      </button>
    </form>
  </div>
</body>
</html>

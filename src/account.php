<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';
require_once 'lang.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$langCode = $_SESSION['lang'] ?? 'en';
$t = $lang[$langCode];

$user_id = $_SESSION['user_id'];

// Handle 2FA toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_2fa'])) {
        $enable_2fa = isset($_POST['is_2fa_enabled']) ? 1 : 0;
        $stmt = $conn->prepare("UPDATE tradehub_users SET is_2fa_enabled = ? WHERE id = ?");
        $stmt->bind_param("ii", $enable_2fa, $user_id);
        $stmt->execute();
    }

    if (isset($_POST['language']) && in_array($_POST['language'], ['en', 'al'])) {
        $_SESSION['lang'] = $_POST['language'];
        header("Location: account.php"); // refresh to apply
        exit();
    }
}

$stmt = $conn->prepare("SELECT name, email, phone, location, created_at, last_login_at, is_2fa_enabled FROM tradehub_users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    die("User not found.");
}

$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="<?= $langCode ?>">
<head>
  <meta charset="UTF-8">
  <title><?= $t['account'] ?> | TradeHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
  <div class="max-w-4xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">👤 <?= $t['account'] ?></h1>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
      <h2 class="text-xl font-semibold mb-4">📄 Profile Information</h2>
      <p class="mb-2"><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
      <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
      <p class="mb-2"><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? 'Not set') ?></p>
      <p class="mb-2"><strong>Location:</strong> <?= htmlspecialchars($user['location'] ?? 'Not set') ?></p>
      <p class="mb-2"><strong>Join Date:</strong> <?= htmlspecialchars($user['created_at']) ?></p>
      <p class="mb-2"><strong>Last Login:</strong> <?= htmlspecialchars($user['last_login_at'] ?? 'Unknown') ?></p>
      <p class="mb-4"><strong>2FA Status:</strong> <?= $user['is_2fa_enabled'] ? '✅ Enabled' : '❌ Disabled' ?></p>

      <form method="POST">
        <label class="inline-flex items-center mt-4">
          <input type="checkbox" name="is_2fa_enabled" class="form-checkbox h-5 w-5 text-blue-600" <?= $user['is_2fa_enabled'] ? 'checked' : '' ?>>
          <span class="ml-2 text-gray-700">Enable Two-Factor Authentication (2FA)</span>
        </label>
        <div class="mt-4">
          <button type="submit" name="toggle_2fa" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Save Settings
          </button>
        </div>
      </form>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
      <h2 class="text-xl font-semibold mb-4">🌐 <?= $t['language'] ?></h2>
      <form method="POST">
        <select name="language" onchange="this.form.submit()" class="border px-4 py-2 rounded">
          <option value="en" <?= $langCode === 'en' ? 'selected' : '' ?>>🇬🇧 English</option>
          <option value="al" <?= $langCode === 'al' ? 'selected' : '' ?>>🇦🇱 Shqip</option>
        </select>
      </form>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
      <h2 class="text-xl font-semibold mb-4">🔒 Security</h2>
      <div class="space-y-3">
        <a href="change_password.php" class="text-blue-600 hover:underline block">Change Password</a>
        <a href="delete_account.php" class="text-red-600 hover:underline block">Delete Account</a>
      </div>
    </div>

    <div class="mt-6">
      <a href="home.php" class="text-gray-600 underline">← <?= $t['home'] ?></a>
    </div>
  </div>
</body>
</html>

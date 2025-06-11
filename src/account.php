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

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_2fa'])) {
        $enable_2fa = isset($_POST['is_2fa_enabled']) ? 1 : 0;
        $stmt = $conn->prepare("UPDATE tradehub_users SET is_2fa_enabled = ? WHERE id = ?");
        $stmt->bind_param("ii", $enable_2fa, $user_id);
        $stmt->execute();
    }

    if (isset($_POST['language']) && in_array($_POST['language'], ['en', 'al'])) {
        $_SESSION['lang'] = $_POST['language'];
        header("Location: account.php");
        exit();
    }

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/profile_pics/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed)) {
            die("Invalid image format.");
        }

        $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetPath)) {
            $stmt = $conn->prepare("UPDATE tradehub_users SET profile_picture = ? WHERE id = ?");
            $stmt->bind_param("si", $targetPath, $user_id);
            $stmt->execute();
            header("Location: account.php?upload=success");
            exit();
        } else {
            die("Failed to upload image.");
        }
    }
}

$stmt = $conn->prepare("SELECT name, email, phone, location, created_at, last_login_at, is_2fa_enabled, profile_picture FROM tradehub_users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    die("User not found.");
}
$user = $result->fetch_assoc();

$profilePic = $user['profile_picture'] 
    ? htmlspecialchars($user['profile_picture']) 
    : "https://ui-avatars.com/api/?name=" . urlencode($user['name']) . "&background=random";

$checkVerify = $conn->prepare("SELECT status FROM seller_verification_requests WHERE user_id = ?");
$checkVerify->bind_param("i", $user_id);
$checkVerify->execute();
$verifyResult = $checkVerify->get_result()->fetch_assoc();
$verifyStatus = $verifyResult['status'] ?? null;
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
   <header class="bg-white border-b shadow-sm sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row items-center justify-between">
    <div class="flex items-center gap-2">
      <h1 class="text-2xl font-bold text-black">TradeHub</h1>
      <span class="text-sm text-gray-400 hidden sm:inline">| Marketplace</span>
    </div>
    <div class="text-center text-sm text-gray-600 mt-2 sm:mt-0">
      👋 <?= $t['hello'] ?? 'Hello' ?>, <strong><?= htmlspecialchars($user['name']) ?></strong>
      <span class="hidden sm:inline ml-2">⏰ <span id="clock"></span></span>
    </div>
    <nav class="mt-3 sm:mt-0 flex flex-wrap justify-center gap-3 text-sm bg-gray-100 px-3 py-2 rounded-full shadow-inner border">
      <a href="home.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">🏠 <?= $t['home'] ?? 'Home' ?></a>
      <a href="inbox.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">💬 <?= $t['messages'] ?? 'Messages' ?></a>
      <a href="my_listings.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">📦 <?= $t['my_listings'] ?></a>
      <a href="my_favorites.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">❤️Favorites</a>
      <a href="list_product.php" class="px-4 py-2 rounded-full font-medium bg-black text-white hover:bg-gray-800 transition">➕ <?= $t['list'] ?? 'List Product' ?></a>
      <a href="logout.php" class="px-4 py-2 rounded-full font-medium text-red-600 hover:underline">🚪 <?= $t['logout'] ?? 'Logout' ?></a>
    </nav>
  </div>
</header>

  <div class="max-w-4xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">👤 <?= $t['account'] ?></h1>

    <!-- Profile Info -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
      <h2 class="text-xl font-semibold mb-4">📄 <?= $t['profile_info'] ?></h2>

      <div class="mb-6 flex items-center gap-4">
        <img src="<?= $profilePic ?>" alt="Profile Picture" class="w-24 h-24 rounded-full object-cover border">
        <form method="POST" enctype="multipart/form-data">
          <label class="block text-sm font-medium mb-1"><?= $t['change_picture'] ?></label>
          <input type="file" name="profile_picture" accept=".jpg,.jpeg,.png" required class="text-sm">
          <button type="submit" class="mt-2 bg-black text-white px-3 py-1 rounded hover:bg-gray-800 text-sm"><?= $t['upload'] ?></button>
        </form>
      </div>

      <p class="mb-2"><strong><?= $t['name'] ?>:</strong> <?= htmlspecialchars($user['name']) ?></p>
      <p class="mb-2"><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
      <p class="mb-2"><strong><?= $t['phone'] ?? 'Phone' ?>:</strong> <?= htmlspecialchars($user['phone'] ?? 'Not set') ?></p>
      <p class="mb-2"><strong><?= $t['location'] ?? 'Location' ?>:</strong> <?= htmlspecialchars($user['location'] ?? 'Not set') ?></p>
      <p class="mb-2"><strong><?= $t['join_date'] ?></strong> <?= htmlspecialchars($user['created_at']) ?></p>
      <p class="mb-2"><strong><?= $t['last_login'] ?></strong> <?= htmlspecialchars($user['last_login_at'] ?? 'Unknown') ?></p>
      <p class="mb-4"><strong><?= $t['2fa_status'] ?></strong> <?= $user['is_2fa_enabled'] ? '✅ ' . $t['enabled'] : '❌ ' . $t['disabled'] ?></p>

      <form method="POST">
        <label class="inline-flex items-center mt-4">
          <input type="checkbox" name="is_2fa_enabled" class="form-checkbox h-5 w-5 text-blue-600" <?= $user['is_2fa_enabled'] ? 'checked' : '' ?>>
          <span class="ml-2 text-gray-700"><?= $t['enable_2fa'] ?></span>
        </label>
        <div class="mt-4">
          <button type="submit" name="toggle_2fa" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            <?= $t['save_settings'] ?>
          </button>
        </div>
      </form>
    </div>

    <!-- Language -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
      <h2 class="text-xl font-semibold mb-4">🌐 <?= $t['language'] ?></h2>
      <form method="POST">
        <select name="language" onchange="this.form.submit()" class="border px-4 py-2 rounded">
          <option value="en" <?= $langCode === 'en' ? 'selected' : '' ?>>🇬🇧 English</option>
          <option value="al" <?= $langCode === 'al' ? 'selected' : '' ?>>🇦🇱 Shqip</option>
        </select>
      </form>
    </div>

    <!-- Verified Seller -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
      <h2 class="text-xl font-semibold mb-4">✅ <?= $t['verified_section'] ?></h2>
      <?php if ($verifyStatus === 'approved'): ?>
        <p class="text-green-600">🎉 <?= $t['verified_success'] ?></p>
        <a href="profile.php?user_id=<?= $user_id ?>" class="text-blue-600 hover:underline block mt-2 text-sm">
          👤 <?= $t['my_seller_profile'] ?>
        </a>
      <?php elseif ($verifyStatus === 'pending'): ?>
        <p class="text-yellow-600">⏳ <?= $t['verification_pending'] ?></p>
      <?php else: ?>
        <form action="apply_verified.php" method="POST" enctype="multipart/form-data" class="space-y-4">
          <div>
            <label class="block font-medium"><?= $t['upload_id_doc'] ?></label>
            <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required class="mt-1 block w-full border rounded p-2">
          </div>
          <button type="submit" class="bg-black text-white px-4 py-2 rounded hover:bg-gray-800">
            <?= $t['submit_verification'] ?>
          </button>
        </form>
      <?php endif; ?>
    </div>

    <!-- Security -->
    <div class="bg-white rounded-lg shadow-md p-6">
      <h2 class="text-xl font-semibold mb-4">🔒 <?= $t['security'] ?></h2>
      <div class="space-y-3">
        <a href="change_password.php" class="text-blue-600 hover:underline block"><?= $t['change_password'] ?></a>
        <a href="delete_account.php" class="text-red-600 hover:underline block"><?= $t['delete_account'] ?></a>
      </div>
    </div>


  </div>
</body>
</html>

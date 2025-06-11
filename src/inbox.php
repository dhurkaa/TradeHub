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

$user_id = $_SESSION['user_id'];
$langCode = $_SESSION['lang'] ?? 'en';
$t = $lang[$langCode];

// Fetch logged-in user's name
$user = $conn->query("SELECT name FROM tradehub_users WHERE id = $user_id")->fetch_assoc();

// Get unique senders who messaged this user (latest message only)
$stmt = $conn->prepare("
  SELECT m.sender_id, u.name, MAX(m.timestamp) AS last_time, 
         (SELECT message FROM messages WHERE sender_id = m.sender_id AND receiver_id = ? ORDER BY timestamp DESC LIMIT 1) AS last_message
  FROM messages m
  JOIN tradehub_users u ON u.id = m.sender_id
  WHERE m.receiver_id = ?
  GROUP BY m.sender_id
  ORDER BY last_time DESC
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="<?= $langCode ?>">
<head>
  <meta charset="UTF-8">
  <title>Inbox | TradeHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 min-h-screen">

<!-- ✅ Navbar directly embedded -->
<header class="bg-white border-b shadow-sm sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row items-center justify-between">
    <div class="flex items-center gap-2">
      <h1 class="text-2xl font-bold text-black">TradeHub</h1>
      <span class="text-sm text-gray-400 hidden sm:inline">| Marketplace</span>
    </div>
    <div class="text-center text-sm text-gray-600 mt-2 sm:mt-0">
      <span>👋 <?= $t['hello'] ?>, <strong><?= htmlspecialchars($user['name']) ?></strong></span>
      <span class="hidden sm:inline ml-2">⏰ <span id="clock"></span></span>
    </div>
    <nav class="mt-3 sm:mt-0 flex flex-wrap justify-center gap-3 text-sm bg-gray-100 px-3 py-2 rounded-full shadow-inner border">
      <a href="home.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">🏠 <?= $t['home'] ?></a>
      <a href="my_favorites.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">❤️ <?= $t['favorites'] ?? 'Favorites' ?></a>
      <a href="account.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">👤 <?= $t['account'] ?></a>
      <a href="my_listings.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">📦 <?= $t['my_listings'] ?></a>
      <a href="list_product.php" class="px-4 py-2 rounded-full font-medium bg-black text-white hover:bg-gray-800 transition">➕ <?= $t['list'] ?></a>
      <a href="logout.php" class="px-4 py-2 rounded-full font-medium text-red-600 hover:underline">🚪 <?= $t['logout'] ?></a>
    </nav>
  </div>
</header>

<!-- 📨 Inbox Content -->
<div class="max-w-3xl mx-auto p-6 mt-10">
  <h1 class="text-3xl font-bold mb-6">📥 <?= $t['inbox'] ?? 'Inbox' ?></h1>

  <?php if ($result && $result->num_rows > 0): ?>
    <ul class="space-y-4">
      <?php while ($row = $result->fetch_assoc()): ?>
        <li class="bg-white rounded-lg shadow p-4 flex justify-between items-center">
          <div>
            <p class="font-semibold"><?= htmlspecialchars($row['name']) ?></p>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($row['last_message']) ?></p>
            <p class="text-xs text-gray-400 mt-1"><?= date('M d, H:i', strtotime($row['last_time'])) ?></p>
          </div>
          <a href="chat.php?user=<?= $row['sender_id'] ?>" class="text-white bg-black px-4 py-2 rounded hover:bg-gray-800">Reply</a>
        </li>
      <?php endwhile; ?>
    </ul>
  <?php else: ?>
    <p class="text-gray-600"><?= $t['no_messages'] ?? 'You have no messages yet.' ?></p>
  <?php endif; ?>

  <div class="mt-6">
    <a href="home.php" class="text-gray-600 underline">← <?= $t['back_to_dashboard'] ?? 'Back to Dashboard' ?></a>
  </div>
</div>

<script>
  setInterval(() => {
    const clock = document.getElementById('clock');
    if (clock) clock.textContent = new Date().toLocaleTimeString();
  }, 1000);
</script>

</body>
</html>

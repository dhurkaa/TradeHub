<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'config.php';
require_once 'lang.php';

$langCode = $_SESSION['lang'] ?? 'en';
$t = $lang[$langCode];

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT name FROM tradehub_users WHERE id = $user_id")->fetch_assoc();
$result = $conn->query("SELECT * FROM products WHERE user_id = $user_id ORDER BY created_at DESC");

$color_classes = ['bg-blue-100', 'bg-orange-100', 'bg-purple-100', 'bg-sky-100'];
?>
<!DOCTYPE html>
<html lang="<?= $langCode ?>">
<head>
  <meta charset="UTF-8" />
  <title>My Listings | TradeHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
  </style>
  <script>
    setInterval(() => {
      const clock = document.getElementById('clock');
      if (clock) clock.textContent = new Date().toLocaleTimeString();
    }, 1000);
  </script>
</head>
<body class="bg-white text-gray-900">

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
      <a href="account.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">👤 <?= $t['account'] ?? 'Account' ?></a>
      <a href="list_product.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">➕ <?= $t['list'] ?></a>
      <a href="my_favorites.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">❤️ <?= $t['favorites'] ?? 'Favorites' ?></a>
      <a href="logout.php" class="px-4 py-2 rounded-full font-medium text-red-600 hover:underline">🚪 <?= $t['logout'] ?? 'Logout' ?></a>
    </nav>
    </nav>
  </div>
</header>

<div class="max-w-6xl mx-auto px-4 py-10">
  <h1 class="text-3xl font-bold mb-6">📦 <?= $t['my_listings'] ?? 'My Listings' ?></h1>

  <?php if ($result && $result->num_rows > 0): ?>
    <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
      <?php
        $i = 0;
        while ($product = $result->fetch_assoc()):
          $color = $color_classes[$i % count($color_classes)];
          $images = json_decode($product['images'], true);
          if (!is_array($images)) {
              $single = trim($product['images']);
              $images = ($single !== '') ? [$single] : [];
          }
          $thumbnail = '/tradehub/uploads/no-image.jpg';
          foreach ($images as $img) {
              if (is_string($img)) {
                  if (!str_starts_with($img, '/')) {
                      $img = '/tradehub/' . ltrim($img, '/');
                  }
                  $thumbnail = htmlspecialchars($img);
                  break;
              }
          }
      ?>
      <div class="rounded-xl shadow-sm <?= $color ?> hover:shadow-lg transition p-5 flex flex-col items-center text-center">
        <img src="<?= $thumbnail ?>" alt="Product Image" class="w-28 h-28 object-cover rounded-lg mb-4">
        <h3 class="text-lg font-semibold"><?= htmlspecialchars($product['title']) ?></h3>
        <p class="text-gray-700 mt-1 mb-3">Price: $<?= number_format($product['price'], 2) ?></p>
        <div class="flex gap-3">
          <a href="product.php?id=<?= $product['id'] ?>" class="bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition">View</a>
          <a href="delete_product.php?id=<?= $product['id'] ?>" onclick="return confirm('Are you sure you want to delete this product?');" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">Delete</a>
          <a href="boost.php?id=<?= $product['id'] ?>" class="bg-yellow-400 text-black px-4 py-2 rounded-lg hover:bg-yellow-500 transition">🚀 Boost</a>

        </div>
      </div>
      <?php $i++; endwhile; ?>
    </div>
  <?php else: ?>
    <p class="text-gray-600 mt-4"><?= $t['no_products'] ?? "You haven't listed any products yet." ?></p>
  <?php endif; ?>
</div>

</body>
</html>

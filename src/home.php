<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'lang.php';
$langCode = $_SESSION['lang'] ?? 'en';
$t = $lang[$langCode];

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT name FROM tradehub_users WHERE id = $user_id")->fetch_assoc();
$productResult = $conn->query("SELECT * FROM products WHERE user_id != $user_id ORDER BY created_at DESC");

$color_classes = ['bg-blue-100', 'bg-orange-100', 'bg-purple-100', 'bg-sky-100'];
$categories = $t['categories'];

$user_data = $conn->query("SELECT location FROM tradehub_users WHERE id = $user_id")->fetch_assoc();
$location = $user_data['location'] ?? '';
$parts = explode(',', $location);
$country = isset($parts[1]) ? trim($parts[1]) : '';

$cityResult = $conn->query("SELECT DISTINCT location FROM tradehub_users WHERE location LIKE '%$country%'");
$cities = [];
while ($row = $cityResult->fetch_assoc()) {
    $locParts = explode(',', $row['location']);
    if (!empty($locParts[0])) {
        $cities[] = trim($locParts[0]);
    }
}
$cities = array_unique($cities);
?>
<!DOCTYPE html>
<html lang="<?= $langCode ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TradeHub Marketplace</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    .dropdown-content { max-height: 0; overflow: hidden; transition: max-height 0.4s ease; }
    .dropdown-content.show { max-height: 500px; }
  </style>
  <script>
    setInterval(() => {
      const clock = document.getElementById('clock');
      if (clock) clock.textContent = new Date().toLocaleTimeString();
    }, 1000);

    function toggleDropdown(index) {
      const content = document.getElementById('dropdown-' + index);
      content.classList.toggle('show');
    }

    function toggleMobileCategories() {
      const menu = document.getElementById('mobile-category-menu');
      menu.classList.toggle('hidden');
    }
  </script>
</head>
<body class="bg-white text-gray-900">
<!-- Header -->
<header class="bg-white border-b shadow-sm sticky top-0 z-[999]">
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
      <a href="inbox.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">💬 <?= $t['messages'] ?></a>
      <a href="account.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">👤 <?= $t['account'] ?></a>
      <a href="my_favorites.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">❤️ <?= $t['favorites'] ?? 'Favorites' ?></a>
      <a href="my_listings.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">📦 <?= $t['my_listings'] ?></a>
      <a href="list_product.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">➕ <?= $t['list'] ?></a>
      <a href="logout.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">🚪 <?= $t['logout'] ?></a>
    </nav>
  </div>
</header>

<!-- Layout Container with 10/80/10 split -->
<div class="flex max-w-[2000px] mx-auto px-4">
  <!-- Left Ad -->
  <aside class="hidden sm:block flex-[1] pr-4">
    <div class="bg-gray-100 p-4 rounded-lg shadow-md mb-6">
      <a href="https://www.company1.com" target="_blank">
        <img src="https://static.wixstatic.com/media/b538da_11312e9f9f62469aa1abc56f4d4d6487~mv2.jpg/v1/fit/w_500,h_500,q_90/file.jpg"
             alt="Ad 1"
             class="w-full h-60 object-cover rounded-lg">
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-[8] px-4 py-8">
    <!-- App Promo Banner -->
    <div class="bg-gradient-to-r from-blue-100 to-blue-200 p-4 rounded-lg mb-6 text-center shadow-sm">
      📱 <?= $t['download_app'] ?>
      <a href="#" class="text-blue-700 font-semibold underline"><?= $t['download_now'] ?></a>
    </div>

    <!-- Search and City Filter -->
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
      <form method="GET" action="search.php" class="flex w-full sm:max-w-xl">
        <input type="text" name="q" placeholder="<?= $t['search_placeholder'] ?>"
               class="w-full px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-black">
        <button type="submit"
                class="bg-black text-white px-6 py-2 rounded-r-lg hover:bg-gray-800 transition">
          <?= $t['search'] ?? 'Search' ?>
        </button>
      </form>
      <select class="border px-4 py-2 rounded-lg" onchange="location.href='search.php?city=' + this.value">
        <option value=""><?= $t['filter_by_city'] ?></option>
        <?php foreach ($cities as $city): ?>
          <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Browse by Category -->
    <div class="hidden sm:block mb-10">
      <h3 class="text-lg font-semibold mb-4"><?= $t['browse_by_category'] ?></h3>
      <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php $index = 0; ?>
        <?php foreach ($categories as $main => $subs): ?>
          <div class="border rounded-lg p-4 bg-gray-50 shadow-sm">
            <button onclick="toggleDropdown(<?= $index ?>)"
                    class="flex justify-between w-full font-semibold text-left text-black">
              <?= htmlspecialchars($main) ?> <span class="text-gray-400">▼</span>
            </button>
            <ul id="dropdown-<?= $index ?>" class="dropdown-content mt-2 space-y-1 text-sm text-gray-700">
              <?php foreach ($subs as $sub): ?>
                <li>
                  <a href="search.php?category=<?= urlencode(strip_tags($sub)) ?>"
                     class="block px-2 py-1 hover:bg-gray-100 rounded">
                    <?= htmlspecialchars($sub) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php $index++; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Featured Products Section -->
    <section>
      <h2 class="text-2xl font-bold mb-6"><?= $t['featured_products'] ?></h2>
      <?php if ($productResult && $productResult->num_rows > 0): ?>
        <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
          <?php $i = 0; ?>
          <?php while ($product = $productResult->fetch_assoc()): ?>
            <?php
              $is_boosted = $product['boosted_until'] && strtotime($product['boosted_until']) > time();
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
            <div class="relative rounded-xl shadow-sm <?= $color ?> hover:shadow-lg transition p-5 flex flex-col items-center text-center">
              <?php if ($is_boosted): ?>
                <div class="absolute top-0 right-0 bg-yellow-400 text-black text-xs font-bold px-2 py-1 rounded-bl-lg shadow">
                  🚀 <?= $t['boosted'] ?? 'Boosted' ?>
                </div>
              <?php endif; ?>
              <img src="<?= $thumbnail ?>" alt="Product Image"
                   class="w-28 h-28 object-cover rounded-lg mb-4">
              <h3 class="text-lg font-semibold"><?= htmlspecialchars($product['title']) ?></h3>
              <p class="text-gray-700 mt-1 mb-3"><?= $t['price'] ?? 'Price' ?>: $<?= number_format($product['price'], 2) ?></p>
              <button onclick="location.href='product.php?id=<?= $product['id'] ?>'"
                      class="bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition">
                <?= $t['view'] ?>
              </button>
            </div>
            <?php $i++; ?>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-600 mt-4"><?= $t['no_products'] ?></p>
      <?php endif; ?>
    </section>

    <!-- Recommendation Loader -->
    <div id="recommendations" class="mt-12"></div>
  </main>

  <!-- Right Ad -->
  <aside class="hidden sm:block flex-[1] pl-4">
    <div class="bg-gray-100 p-4 rounded-lg shadow-md mb-6">
      <a href="#" target="_blank">
        <img src="https://www.magazaonline.co.uk/cdn/shop/files/frutex_golden_eagle_250ml_new_25_2000x.png?v=1737709831"
             alt="Ad 2"
             class="w-full h-60 object-cover rounded-lg">
      </a>
    </div>
  </aside>
</div>

<!-- Footer -->
<footer class="bg-white border-t mt-12 py-6 text-center text-sm text-gray-500">
  <p class="font-semibold text-gray-800">TradeHub — The Marketplace That Moves the World.</p>
  <p class="mt-1">&copy; <?= date('Y') ?> TradeHub. All rights reserved.</p>
</footer>


<!-- Message-popup checker -->
<script>
let popupOpen = false;
function checkMessages() {
  fetch("check_new_messages.php")
    .then(res => res.json())
    .then(data => {
      if (data && data.message && data.sender_id && !popupOpen) {
        popupOpen = true;

        const popup = document.createElement('div');
        popup.id  = 'msg-popup';
        popup.className = "fixed bottom-4 right-4 bg-white border border-gray-300 w-80 p-4 rounded-lg shadow-lg z-[999]";

        popup.innerHTML = `
          <div class="flex justify-between items-center mb-2">
            <strong><?= $t['message_from'] ?? 'Message from' ?> ${data.name}</strong>
            <button onclick="document.getElementById('msg-popup').remove(); popupOpen = false;"
                    class="text-gray-500 hover:text-red-500 text-lg font-bold">&times;</button>
          </div>
          <div class="text-sm text-gray-800 mb-3">${data.message}</div>
          <a href="chat.php?user=${data.sender_id}"
             class="block text-center bg-black text-white py-2 rounded hover:bg-gray-800">
            <?= $t['reply'] ?? 'Reply' ?>
          </a>
        `;
        document.body.appendChild(popup);
      }
    })
    .catch(() => {/* ignore */});
}
setInterval(checkMessages, 5000);
</script>

<!-- Load dynamic recommendations -->
<script>
fetch("recommendations.php")
  .then(res => res.text())
  .then(html => {
    document.getElementById("recommendations").innerHTML = html;
  })
  .catch(() => {/* ignore */});
</script>

</body>
</html>

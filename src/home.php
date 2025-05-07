<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT name FROM tradehub_users WHERE id = $user_id")->fetch_assoc();
$productResult = $conn->query("SELECT * FROM products WHERE user_id != $user_id ORDER BY created_at DESC");

$color_classes = ['bg-blue-100', 'bg-orange-100', 'bg-purple-100', 'bg-sky-100'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>TradeHub Marketplace</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    setInterval(() => {
      const clock = document.getElementById('clock');
      if (clock) clock.textContent = new Date().toLocaleTimeString();
    }, 1000);
  </script>
</head>
<body class="bg-white text-gray-900">
  <!-- Header -->
 <header class="bg-white border-b shadow-sm sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row items-center justify-between">
    
    <!-- Left: Logo -->
    <div class="flex items-center gap-2">
      <h1 class="text-2xl font-bold text-black">TradeHub</h1>
      <span class="text-sm text-gray-400 hidden sm:inline">| Marketplace</span>
    </div>

    <!-- Center: Time & Hello -->
    <div class="text-center text-sm text-gray-600 mt-2 sm:mt-0">
      <span>👋 Hello, <strong><?= htmlspecialchars($user['name']) ?></strong></span>
      <span class="hidden sm:inline ml-2">⏰ <span id="clock"></span></span>
    </div>

    <!-- Right: Navigation -->
    <nav class="mt-3 sm:mt-0 flex flex-wrap justify-center gap-2 text-sm">
      <a href="home.php" class="px-3 py-1 rounded hover:bg-gray-100 transition">Home</a>
      <a href="inbox.php" class="px-3 py-1 rounded hover:bg-gray-100 transition">Messages</a>
      <a href="account.php" class="px-3 py-1 rounded hover:bg-gray-100 transition">Account</a>
      <a href="my_listings.php" class="px-3 py-1 border border-black text-black rounded hover:bg-black hover:text-white transition">My Listings</a>
      <a href="list_product.php" class="px-3 py-1 bg-black text-white rounded hover:bg-gray-800 transition">+ List Product</a>
      <a href="logout.php" class="px-3 py-1 text-red-600 hover:underline">Sign Out</a>
    </nav>
  </div>
</header>


  <!-- Main Content -->
  <main class="max-w-7xl mx-auto px-4 py-8">
    
    <!-- App Promo -->
    <div class="bg-gradient-to-r from-blue-100 to-blue-200 p-4 rounded-lg mb-6 text-center shadow-sm">
      📱 Get the <strong>TradeHub</strong> app on your phone!
      <a href="#" class="text-blue-700 font-semibold underline">Download now</a>
    </div>

    <!-- Search and City Filter -->
    <div class="flex flex-col sm:flex-row justify-between items-center mb-10 gap-4">
      <form method="GET" action="search.php" class="flex w-full sm:max-w-xl">
        <input type="text" name="q" placeholder="Search for products"
               class="w-full px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-black">
        <button type="submit"
                class="bg-black text-white px-6 py-2 rounded-r-lg hover:bg-gray-800 transition">Search</button>
      </form>
      <select class="border px-4 py-2 rounded-lg" onchange="location.href='search.php?city=' + this.value">
        <option value="">Filter by City</option>
        <option value="Tirana">Tirana</option>
        <option value="Pristina">Pristina</option>
        <option value="Skopje">Skopje</option>
      </select>
    </div>

    <!-- Categories -->
    <section class="mb-12">
      <h3 class="text-xl font-semibold mb-6">Shop by Category</h3>
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-4 text-center">
        <?php
        $categories = [
          ['name' => 'Electronics', 'icon' => '💻'],
          ['name' => 'Clothing', 'icon' => '👕'],
          ['name' => 'Home', 'icon' => '🛋️'],
          ['name' => 'Accessories', 'icon' => '⌚'],
          ['name' => 'Sports', 'icon' => '⚽'],
          ['name' => 'Toys', 'icon' => '🧸'],
        ];
        foreach ($categories as $cat):
        ?>
        <a href="search.php?q=<?= urlencode($cat['name']) ?>"
           class="flex flex-col items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-100 transition">
          <span class="text-3xl mb-2"><?= $cat['icon'] ?></span>
          <span class="text-sm font-medium"><?= $cat['name'] ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- Featured Products -->
    <section>
      <h2 class="text-2xl font-bold mb-6">Featured Products</h2>
      <?php if ($productResult && $productResult->num_rows > 0): ?>
        <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
          <?php $i = 0; ?>
          <?php while ($product = $productResult->fetch_assoc()): ?>
            <?php $color = $color_classes[$i % count($color_classes)]; ?>
            <div class="rounded-xl shadow-sm <?= $color ?> hover:shadow-lg transition p-5 flex flex-col items-center text-center">
              <img src="<?= !empty($product['image_url']) ? htmlspecialchars($product['image_url']) : 'https://via.placeholder.com/150?text=No+Image' ?>"
                   alt="Product Image"
                   class="w-28 h-28 object-cover rounded-lg mb-4">
              <h3 class="text-lg font-semibold"><?= htmlspecialchars($product['title']) ?></h3>
              <p class="text-gray-700 mt-1 mb-3">Price: $<?= number_format($product['price'], 2) ?></p>
              <button onclick="location.href='product.php?id=<?= $product['id'] ?>'"
                      class="bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition">View</button>
            </div>
            <?php $i++; ?>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-600 mt-4">No products have been listed yet.</p>
      <?php endif; ?>
    </section>
  </main>

  <!-- Footer -->
  <footer class="bg-gray-100 mt-12 py-4 text-center text-sm text-gray-600">
    &copy; <?= date('Y') ?> TradeHub. All rights reserved.<br>
  </footer>

  <!-- Styles -->
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
    }
    .blue    { background-color: #d7e8ff; }
    .orange  { background-color: #ffe3b3; }
    .purple  { background-color: #ead7ff; }
    .sky     { background-color: #d4f1ff; }

    .blue button,
    .purple button {
      background-color: #4a4aff;
      color: #fff;
    }

    .orange button,
    .sky button {
      background-color: #ff7b2e;
      color: #fff;
    }

    .blue button:hover,
    .purple button:hover {
      background-color: #3737e0;
    }

    .orange button:hover,
    .sky button:hover {
      background-color: #e85e0c;
    }
  </style>

  <!-- Real-Time Message Popup -->
  <script>
let popupOpen = false;
function checkMessages() {
  fetch("check_new_messages.php")
    .then(res => res.json())
    .then(data => {
      if (data && data.message && data.sender_id && !popupOpen) {
        popupOpen = true;
        const popup = document.createElement('div');
        popup.id = 'msg-popup';
        popup.className = "fixed bottom-4 right-4 bg-white border border-gray-300 w-80 p-4 rounded-lg shadow-lg z-50";
        popup.innerHTML = `
          <div class="flex justify-between items-center mb-2">
            <strong>Message from ${data.name}</strong>
            <button onclick="document.getElementById('msg-popup').remove(); popupOpen = false;" class="text-gray-500 hover:text-red-500">&times;</button>
          </div>
          <div class="text-sm text-gray-800 mb-3">${data.message}</div>
          <a href="chat.php?user=${data.sender_id}" class="block text-center bg-black text-white py-2 rounded hover:bg-gray-800">Reply</a>
        `;
        document.body.appendChild(popup);
      }
    });
}

setInterval(checkMessages, 5000); // check every 5 sec
</script>

</body>
</html>

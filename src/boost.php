<?php
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
$product_id = intval($_GET['id']);
$product = $conn->query("SELECT * FROM products WHERE id = $product_id AND user_id = $user_id")->fetch_assoc();

if (!$product) {
    die("Invalid product or unauthorized access.");
}
?>

<!DOCTYPE html>
<html lang="<?= $langCode ?>">
<head>
  <meta charset="UTF-8">
  <title>Boost Listing | TradeHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>.hidden { display: none; }</style>
</head>
<body class="bg-gray-50 text-gray-900">

<!-- Navbar -->
<header class="bg-white border-b shadow-sm sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row items-center justify-between">
    <div class="flex items-center gap-2">
      <h1 class="text-2xl font-bold text-black">TradeHub</h1>
      <span class="text-sm text-gray-400 hidden sm:inline">| Marketplace</span>
    </div>
    <div class="text-center text-sm text-gray-600 mt-2 sm:mt-0">
      👋 <?= $t['hello'] ?? 'Hello' ?>, <strong><?= htmlspecialchars($_SESSION['name'] ?? '') ?></strong>
    </div>
    <nav class="mt-3 sm:mt-0 flex flex-wrap justify-center gap-3 text-sm bg-gray-100 px-3 py-2 rounded-full shadow-inner border">
      <a href="home.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">🏠 <?= $t['home'] ?? 'Home' ?></a>
      <a href="inbox.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">💬 <?= $t['messages'] ?? 'Messages' ?></a>
      <a href="account.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">👤 <?= $t['account'] ?? 'Account' ?></a>
      <a href="my_favorites.php" class="px-4 py-2 rounded-full font-medium border border-black text-black hover:bg-red-800 transition">❤️ Favorites</a>
      <a href="list_product.php" class="px-4 py-2 rounded-full font-medium bg-black text-white hover:bg-gray-800 transition">➕ <?= $t['list'] ?? 'List Product' ?></a>
      <a href="logout.php" class="px-4 py-2 rounded-full font-medium text-red-600 hover:underline">🚪 <?= $t['logout'] ?? 'Logout' ?></a>
    </nav>
  </div>
</header>

<!-- Boost Panel -->
<div class="max-w-2xl mx-auto mt-10 bg-white p-8 rounded-2xl shadow-lg">
  <h2 class="text-2xl font-bold mb-4">🚀 Boost Your Listing</h2>
  <p class="mb-6 text-gray-600">You're about to boost: <strong><?= htmlspecialchars($product['title']) ?></strong></p>

  <form action="redirect_payment.php" method="POST" class="space-y-4" id="boostForm">
    <input type="hidden" name="product_id" value="<?= $product_id ?>">

    <!-- Duration -->
    <div>
      <label class="font-semibold block mb-1">🗓️ Duration</label>
      <select name="duration" id="duration" class="w-full px-4 py-2 border rounded-lg" required>
        <option value="3" data-base="2.49">3 Days</option>
        <option value="7" data-base="4.99">7 Days</option>
        <option value="14" data-base="8.99">14 Days</option>
      </select>
    </div>

    <!-- Placement -->
    <div>
      <label class="font-semibold block mb-1">📍 Placement</label>
      <select name="placement" class="w-full px-4 py-2 border rounded-lg" required>
        <option value="homepage">Homepage</option>
        <option value="category">Category Top</option>
        <option value="both">Homepage + Category</option>
      </select>
    </div>

    <!-- Radius -->
    <div>
      <label class="font-semibold block mb-1">🌍 Boost Radius</label>
      <select name="range_km" id="range_km" class="w-full px-4 py-2 border rounded-lg" required>
        <option value="10" data-multiplier="1">10 KM (City)</option>
        <option value="50" data-multiplier="1.8">50 KM (Nearby Cities)</option>
        <option value="150" data-multiplier="2.5">Whole Kosovo</option>
      </select>
    </div>

    <!-- Payment -->
    <div>
      <label class="font-semibold block mb-1">💳 Payment Method</label>
      <select name="payment_method" class="w-full px-4 py-2 border rounded-lg" required>
        <option value="paypal">PayPal</option>
        <option value="paysera">Paysera</option>
        <option value="revolut">Revolut</option>
      </select>
    </div>

    <!-- Dynamic Price -->
    <div class="text-right text-sm text-gray-600">Final Price: <span id="finalPrice" class="font-bold text-black">€2.49</span></div>

    <button type="submit" class="bg-yellow-400 text-black font-semibold px-6 py-3 rounded-lg hover:bg-yellow-500 w-full transition">
      ✅ Confirm & Pay
    </button>
  </form>
</div>

<script>
  const durationSelect = document.getElementById('duration');
  const rangeSelect = document.getElementById('range_km');
  const priceLabel = document.getElementById('finalPrice');

  function updatePrice() {
    const basePrice = parseFloat(durationSelect.options[durationSelect.selectedIndex].dataset.base);
    const multiplier = parseFloat(rangeSelect.options[rangeSelect.selectedIndex].dataset.multiplier);
    const final = (basePrice * multiplier).toFixed(2);
    priceLabel.textContent = "€" + final;
  }

  durationSelect.addEventListener('change', updatePrice);
  rangeSelect.addEventListener('change', updatePrice);
  updatePrice();
</script>

</body>
</html>

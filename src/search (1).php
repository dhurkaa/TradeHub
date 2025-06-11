<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'config.php';
require_once 'lang.php';

$langCode = $_SESSION['lang'] ?? 'en';
$t = $lang[$langCode];

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT name FROM tradehub_users WHERE id = $user_id")->fetch_assoc();

$searchTerm = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$city = trim($_GET['city'] ?? '');
$price_min = is_numeric($_GET['price_min'] ?? '') ? floatval($_GET['price_min']) : null;
$price_max = is_numeric($_GET['price_max'] ?? '') ? floatval($_GET['price_max']) : null;
$sort = $_GET['sort'] ?? '';

$conditions = [];
if ($searchTerm !== '') {
    $safe = $conn->real_escape_string($searchTerm);
    $conditions[] = "(title LIKE '%$safe%' OR description LIKE '%$safe%' OR category LIKE '%$safe%')";
}
if ($category !== '') {
    $safeCat = $conn->real_escape_string($category);
    $conditions[] = "category = '$safeCat'";
}
if ($city !== '') {
    $safeCity = $conn->real_escape_string($city);
    $conditions[] = "location = '$safeCity'";
}
if ($price_min !== null) {
    $conditions[] = "price >= $price_min";
}
if ($price_max !== null) {
    $conditions[] = "price <= $price_max";
}

$where = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';
switch ($sort) {
  case 'price_asc':
    $orderBy = 'ORDER BY price ASC';
    break;
  case 'price_desc':
    $orderBy = 'ORDER BY price DESC';
    break;
  case 'rating_desc':
    $orderBy = 'ORDER BY (
      SELECT AVG(rating) FROM product_reviews WHERE product_id = products.id
    ) DESC';
    break;
  default:
    $orderBy = 'ORDER BY created_at DESC';
}

$query = "SELECT * FROM products $where $orderBy";
$productResult = $conn->query($query);

$categories = array_merge(...array_values($t['categories']));
$cityResult = $conn->query("SELECT DISTINCT location FROM products WHERE location IS NOT NULL AND location != ''");
$cities = [];
while ($row = $cityResult->fetch_assoc()) $cities[] = $row['location'];

function getAvgRating($conn, $product_id) {
    $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating FROM product_reviews WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['avg_rating'] !== null ? round($result['avg_rating'], 1) : null;
}
?>
<!DOCTYPE html>
<html lang="<?= $langCode ?>">
<head>
  <meta charset="UTF-8">
  <title>Search | TradeHub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white text-gray-900">
<header class="bg-white border-b shadow-sm sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row items-center justify-between">
    <div class="flex items-center gap-2">
      <h1 class="text-2xl font-bold text-black">TradeHub</h1>
      <span class="text-sm text-gray-400 hidden sm:inline">| Marketplace</span>
    </div>
    <div class="text-center text-sm text-gray-600 mt-2 sm:mt-0">
      <span>👋 <?= $t['hello'] ?>, <strong><?= htmlspecialchars($user['name']) ?></strong></span>
    </div>
    <nav class="mt-3 sm:mt-0 flex flex-wrap justify-center gap-3 text-sm bg-gray-100 px-3 py-2 rounded-full shadow-inner border">
      <a href="home.php" class="px-4 py-2 rounded-full font-medium bg-black text-white hover:bg-black">🏠 <?= $t['home'] ?></a>
      <a href="inbox.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">💬 <?= $t['messages'] ?></a>
      <a href="account.php" class="px-4 py-2 rounded-full font-medium hover:bg-black hover:text-white transition">👤 <?= $t['account'] ?></a>
      <a href="my_listings.php" class="px-4 py-2 rounded-full font-medium border border-black text-black hover:bg-black hover:text-white transition">📦 <?= $t['my_listings'] ?></a>
      <a href="list_product.php" class="px-4 py-2 rounded-full font-medium bg-black text-white hover:bg-gray-800 transition">➕ <?= $t['list'] ?></a>
      <a href="my_favorites.php" class="px-4 py-2 rounded-full font-medium bg-black text-white hover:bg-red-800 transition">❤️ Favorites</a>
      <a href="logout.php" class="px-4 py-2 rounded-full font-medium text-red-600 hover:underline">🚪 <?= $t['logout'] ?></a>
    </nav>
  </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-8">
  <form method="GET" class="bg-gray-100 p-5 rounded-lg shadow mb-8 flex flex-wrap gap-4">
    <input type="text" name="q" placeholder="<?= $t['search_placeholder'] ?>" value="<?= htmlspecialchars($searchTerm) ?>" class="px-4 py-2 border border-gray-300 rounded w-full sm:w-auto flex-1">
    <select name="category" class="px-4 py-2 border border-gray-300 rounded w-full sm:w-auto">
      <option value=""><?= $t['category'] ?? 'Category' ?></option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= htmlspecialchars($cat) ?>" <?= $cat == $category ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="city" class="px-4 py-2 border border-gray-300 rounded w-full sm:w-auto">
      <option value=""><?= $t['filter_by_city'] ?? 'City' ?></option>
      <?php foreach ($cities as $c): ?>
        <option value="<?= htmlspecialchars($c) ?>" <?= $c == $city ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="number" step="0.01" name="price_min" placeholder="Min Price" value="<?= htmlspecialchars($_GET['price_min'] ?? '') ?>" class="px-4 py-2 border border-gray-300 rounded w-28">
    <input type="number" step="0.01" name="price_max" placeholder="Max Price" value="<?= htmlspecialchars($_GET['price_max'] ?? '') ?>" class="px-4 py-2 border border-gray-300 rounded w-28">
    <select name="sort" class="px-4 py-2 border border-gray-300 rounded w-full sm:w-auto">
      <option value=""><?= $t['sort_by'] ?? 'Sort by' ?></option>
      <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>><?= $t['price_low_high'] ?? 'Price: Low to High' ?></option>
      <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>><?= $t['price_high_low'] ?? 'Price: High to Low' ?></option>
      <option value="rating_desc" <?= $sort === 'rating_desc' ? 'selected' : '' ?>><?= $t['rating_high_low'] ?? 'Rating: High to Low' ?></option>
    </select>
    <button type="submit" class="bg-black text-white px-6 py-2 rounded hover:bg-gray-800 transition"><?= $t['search'] ?? 'Search' ?></button>
  </form>

  <?php if ($productResult && $productResult->num_rows > 0): ?>
    <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
      <?php while ($product = $productResult->fetch_assoc()): ?>
        <?php
          $images = json_decode($product['images'] ?? '[]', true);
          $thumbnail = !empty($images[0]) ? htmlspecialchars($images[0]) : 'https://via.placeholder.com/120x120?text=No+Image';
          $avgRating = getAvgRating($conn, $product['id']);
        ?>
        <div class="bg-white rounded-xl shadow p-5 flex flex-col items-center text-center hover:shadow-lg transition">
          <img src="<?= $thumbnail ?>" alt="Product Image" class="w-28 h-28 object-cover rounded mb-4">
          <h3 class="text-lg font-semibold"><?= htmlspecialchars($product['title']) ?></h3>
          <p class="text-gray-700 mt-1 mb-2">$<?= number_format($product['price'], 2) ?></p>
          <?php if ($avgRating): ?>
            <p class="text-yellow-500 text-sm mb-2"><?= str_repeat('★', floor($avgRating)) ?> <span class="text-gray-600 text-xs">(<?= $avgRating ?>/5)</span></p>
          <?php endif; ?>
          <button onclick="location.href='product.php?id=<?= $product['id'] ?>'" class="bg-black text-white px-4 py-2 rounded hover:bg-gray-800 transition"><?= $t['view'] ?? 'View' ?></button>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <p class="text-center text-gray-600 text-lg mt-12"><?= $t['no_products'] ?? 'No products found for your filters.' ?></p>
  <?php endif; ?>
</main>

<footer class="bg-gray-100 mt-12 py-4 text-center text-sm text-gray-600">
  &copy; <?= date('Y') ?> TradeHub. All rights reserved.
</footer>
</body>
</html>
